<?php

/**
 * @file
 * Definition of Drupal\xmlsitemap\XmlSitemapGenerator.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Language\LanguageInterface;

/**
 * XmlSitemap generator service.
 */
class XmlSitemapGeneratorService implements XmlSitemapGeneratorInterface {

  public static $aliases;
  public static $last_language;
  public static $memory_start;

  /**
   * {@inheritdoc}
   */
  public function getPathAlias($path, $language) {
    $query = db_select('url_alias', 'u');
    $query->fields('u', array('source', 'alias'));
    if (!isset(static::$aliases)) {
      $query->condition('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED, '=');
      $static::$aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED] = $query->execute()->fetchAllKeyed();
    }
    if ($language != LanguageInterface::LANGCODE_NOT_SPECIFIED && $last_language != $language) {
      unset(static::$aliases[$last_language]);
      $query->condition('langcode', $language, '=');
      $query->orderBy('pid');
      static::$aliases[$language] = $query->execute()->fetchAllKeyed();
    }

    if ($language != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset(static::$aliases[$language][$path])) {
      return static::$aliases[$language][$path];
    }
    elseif (isset(static::$aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED][$path])) {
      return static::$aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED][$path];
    }
    else {
      return $path;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateBefore() {
    // Attempt to increase the memory limit.
    _xmlsitemap_set_memory_limit();

    if (\Drupal::state()->get('developer_mode')) {
      watchdog('xmlsitemap', 'Starting XML sitemap generation. Memory usage: @memory-peak.', array(
        '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
          ), WATCHDOG_DEBUG
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMemoryUsage($start = FALSE) {
    $current = memory_get_peak_usage(TRUE);
    if (!isset(self::$memory_start) || $start) {
      self::$memory_start = $current;
    }
    return $current - self::$memory_start;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptimalMemoryLimit() {
    $optimal_limit = &drupal_static(__FUNCTION__);
    if (!isset($optimal_limit)) {
      // Set the base memory amount from the provided core constant.
      $optimal_limit = parse_size(DRUPAL_MINIMUM_PHP_MEMORY_LIMIT);

      // Add memory based on the chunk size.
      $optimal_limit += xmlsitemap_get_chunk_size() * 500;

      // Add memory for storing the url aliases.
      if (\Drupal::config()->get('prefetch_aliases')) {
        $aliases = db_query("SELECT COUNT(pid) FROM {url_alias}")->fetchField();
        $optimal_limit += $aliases * 250;
      }
    }
    return $optimal_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemoryLimit($new_limit = NULL) {
    $current_limit = @ini_get('memory_limit');
    if ($current_limit && $current_limit != -1) {
      if (!is_null($new_limit)) {
        $new_limit = $this->getOptimalMemoryLimit();
      }
      if (parse_size($current_limit) < $new_limit) {
        return @ini_set('memory_limit', $new_limit);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generatePage(XmlSitemapInterface $sitemap, $page) {
    try {
      module_load_include('xmlsitemap.inc', 'xmlsitemap');
      $writer = new XMLSitemapWriter($sitemap, $page);
      $writer->startDocument();
      $writer->generateXML();
      $writer->endDocument();
    }
    catch (Exception $e) {
      watchdog_exception('xmlsitemap', $e);
      throw $e;
      return FALSE;
    }

    return $writer->getSitemapElementCount();
  }

  /**
   * {@inheritdoc}
   */
  public function generateChunk(XmlSitemapInterface $sitemap, XMLSitemapWriter $writer, $chunk) {
    $lastmod_format = \Drupal::config('xmlsitemap.settings')->get('lastmod_format');

    $url_options = $sitemap->uri['options'];
    $url_options += array(
      'absolute' => TRUE,
      'base_url' => \Drupal::state()->get('base_url'),
      'language' => language_default(),
      'alias' => \Drupal::config('xmlsitemap.settings')->get('prefetch_aliases'),
    );

    $last_url = '';
    $link_count = 0;

    $query = db_select('xmlsitemap', 'x');
    $query->fields('x', array('loc', 'lastmod', 'changefreq', 'changecount', 'priority', 'language', 'access', 'status'));
    $query->condition('x.access', 1);
    $query->condition('x.status', 1);
    $query->orderBy('x.language', 'DESC');
    $query->orderBy('x.loc');
    $query->addTag('xmlsitemap_generate');
    $query->addMetaData('sitemap', $sitemap);

    $offset = max($chunk - 1, 0) * xmlsitemap_get_chunk_size();
    $limit = xmlsitemap_get_chunk_size();
    $query->range($offset, $limit);
    $links = $query->execute();

    while ($link = $links->fetchAssoc()) {
      $link['language'] = $link['language'] != LanguageInterface::LANGCODE_NOT_SPECIFIED ? xmlsitemap_language_load($link['language']) : $url_options['language'];
      if ($url_options['alias']) {
        $link['loc'] = $this->getPathAlias($link['loc'], $link['language']->langcode);
      }
      $link_options = array(
        'language' => $link['language'],
        'xmlsitemap_link' => $link,
        'xmlsitemap_sitemap' => $sitemap,
      );
      // @todo Add a separate hook_xmlsitemap_link_url_alter() here?
      $link_url = url($link['loc'], $link_options + $url_options);

      // Skip this link if it was a duplicate of the last one.
      // @todo Figure out a way to do this before generation so we can report
      // back to the user about this.
      if ($link_url == $last_url) {
        continue;
      }
      else {
        $last_url = $link_url;
        // Keep track of the total number of links written.
        $link_count++;
      }

      $element = array();
      $element['loc'] = $link_url;
      if ($link['lastmod']) {
        $element['lastmod'] = gmdate($lastmod_format, $link['lastmod']);
        // If the link has a lastmod value, update the changefreq so that links
        // with a short changefreq but updated two years ago show decay.
        // We use abs() here just incase items were created on this same cron run
        // because lastmod would be greater than REQUEST_TIME.
        $link['changefreq'] = (abs(REQUEST_TIME - $link['lastmod']) + $link['changefreq']) / 2;
      }
      if ($link['changefreq']) {
        $element['changefreq'] = xmlsitemap_get_changefreq($link['changefreq']);
      }
      if (isset($link['priority']) && $link['priority'] != 0.5) {
        // Don't output the priority value for links that have 0.5 priority. This
        // is the default 'assumed' value if priority is not included as per the
        // sitemaps.org specification.
        $element['priority'] = number_format($link['priority'], 1);
      }
      $writer->writeSitemapElement('url', $element);
    }

    return $link_count;
  }

  /**
   * {@inheritdoc}
   */
  public function generateIndex(XmlSitemapInterface $sitemap) {
    try {
      $writer = new XMLSitemapIndexWriter($sitemap);
      $writer->startDocument();
      $writer->generateXML();
      $writer->endDocument();
    }
    catch (Exception $e) {
      watchdog_exception('xmlsitemap', $e);
      throw $e;
      return FALSE;
    }

    return $writer->getSitemapElementCount();
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateBatch(array $smids = array()) {
    if (empty($smids)) {
      $sitemaps = \Drupal::entityManager()->getStorage('xmlsitemap')->loadMultiple();
      foreach ($sitemaps as $sitemap) {
        $smids[] = $sitemap->id();
      }
    }

    $t = 't';
    $batch = array(
      'operations' => array(),
      //'error_message' => $t('An error has occurred.'),
      'finished' => 'xmlsitemap_regenerate_batch_finished',
      'title' => t('Regenerating Sitemap'),
      'file' => drupal_get_path('module', 'xmlsitemap') . '/xmlsitemap.generate.inc',
    );

    // Set the regenerate flag in case something fails during file generation.
    $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('regenerate_needed' => TRUE)));

    // @todo Get rid of this batch operation.
    $batch['operations'][] = array('_xmlsitemap_regenerate_before', array());

    // Generate all the sitemap pages for each context.
    foreach ($smids as $smid) {
      $batch['operations'][] = array('xmlsitemap_regenerate_batch_generate', array($smid));
      $batch['operations'][] = array('xmlsitemap_regenerate_batch_generate_index', array($smid));
    }

    // Clear the regeneration flag.
    $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('regenerate_needed' => FALSE)));

    return $batch;
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateBatchGenerate($smid, array &$context) {
    if (!isset($context['sandbox']['sitemap'])) {
      $context['sandbox']['sitemap'] = xmlsitemap_sitemap_load($smid);
      $context['sandbox']['sitemap']->chunks = 1;
      $context['sandbox']['sitemap']->links = 0;
      $context['sandbox']['max'] = XMLSITEMAP_MAX_SITEMAP_LINKS;

      // Clear the cache directory for this sitemap before generating any files.
      xmlsitemap_check_directory($context['sandbox']['sitemap']);
      xmlsitemap_clear_directory($context['sandbox']['sitemap']);
    }

    $sitemap = &$context['sandbox']['sitemap'];
    $links = xmlsitemap_generate_page($sitemap, $sitemap->chunks);
    $context['message'] = t('Now generating %sitemap-url.', array('%sitemap-url' => url('sitemap.xml', $sitemap->uri['options'] + array('query' => array('page' => $sitemap->chunks)))));

    if ($links) {
      $sitemap->links += $links;
      $sitemap->chunks++;
    }
    else {
      // Cleanup the 'extra' empty file.
      $file = xmlsitemap_sitemap_get_file($sitemap, $sitemap->chunks);
      if (file_exists($file) && $sitemap->chunks > 1) {
        file_unmanaged_delete($file);
      }
      $sitemap->chunks--;

      // Save the updated chunks and links values.
      $context['sandbox']['max'] = $sitemap->chunks;
      $sitemap->updated = REQUEST_TIME;
      xmlsitemap_sitemap_get_max_filesize($sitemap);
      xmlsitemap_sitemap_save($sitemap);
    }

    if ($sitemap->chunks != $context['sandbox']['max']) {
      $context['finished'] = $sitemap->chunks / $context['sandbox']['max'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateBatchGenerateIndex($smid, array &$context) {
    $sitemap = xmlsitemap_sitemap_load($smid);
    if ($sitemap->chunks > 1) {
      xmlsitemap_generate_index($sitemap);
      $context['message'] = t('Now generating sitemap index %sitemap-url.', array('%sitemap-url' => url('sitemap.xml', $sitemap->uri['options'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function regenerateBatchFinished($success, $results, $operations, $elapsed) {
    if ($success && !\Drupal::config('xmlsitemap.settings')->get('regenerate_needed', FALSE)) {
      \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_generated_last', REQUEST_TIME);
      //drupal_set_message(t('The sitemaps were regenerated.'));
      // Show a watchdog message that the sitemap was regenerated.
      watchdog('xmlsitemap', 'Finished XML sitemap generation in @elapsed. Memory usage: @memory-peak.', array(
        '@elapsed' => $elapsed,
        '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
          ), WATCHDOG_NOTICE
      );
    }
    else {
      drupal_set_message(t('The sitemaps were not successfully regenerated.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildBatch(array $entities, $save_custom = FALSE) {
    $batch = array(
      'operations' => array(),
      'finished' => 'xmlsitemap_rebuild_batch_finished',
      'title' => t('Rebuilding Sitemap'),
      'file' => drupal_get_path('module', 'xmlsitemap') . '/xmlsitemap.generate.inc',
    );

    // Set the rebuild flag in case something fails during the rebuild.
    $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('xmlsitemap_rebuild_needed' => TRUE)));

    // Purge any links first.
    $batch['operations'][] = array('xmlsitemap_rebuild_batch_clear', array($entities, (bool) $save_custom));

    // Fetch all the sitemap links and save them to the {xmlsitemap} table.
    foreach ($entities as $entity) {
      $info = xmlsitemap_get_link_info($entity);
      $batch['operations'][] = array($info['xmlsitemap']['rebuild callback'], array($entity));
    }

    // Clear the rebuild flag.
    $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('xmlsitemap_rebuild_needed' => FALSE)));

    // Add the regeneration batch.
    $regenerate_batch = xmlsitemap_regenerate_batch();
    $batch['operations'] = array_merge($batch['operations'], $regenerate_batch['operations']);

    return $batch;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildBatchClear(array $entities, $save_custom, &$context) {
    if (!empty($entities)) {
      $query = db_delete('xmlsitemap');
      $query->condition('type', $entities);

      // If we want to save the custom data, make sure to exclude any links
      // that are not using default inclusion or priority.
      if ($save_custom) {
        $query->condition('status_override', 0);
        $query->condition('priority_override', 0);
      }

      $query->execute();
    }

    $context['message'] = t('Purging links.');
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildBatchFetch($entity, &$context) {
    if (!isset($context['sandbox']['info'])) {
      $context['sandbox']['info'] = xmlsitemap_get_link_info($entity);
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['last_id'] = 0;
    }
    $info = $context['sandbox']['info'];

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $entity);
    $query->entityCondition('entity_id', $context['sandbox']['last_id'], '>');
    $query->addTag('xmlsitemap_link_bundle_access');
    $query->addTag('xmlsitemap_rebuild');
    $query->addMetaData('entity', $entity);
    $query->addMetaData('entity_info', $info);

    if (!isset($context['sandbox']['max'])) {
      $count_query = clone $query;
      $count_query->count();
      $context['sandbox']['max'] = $count_query->execute();
      if (!$context['sandbox']['max']) {
        // If there are no items to process, skip everything else.
        return;
      }
    }

    // PostgreSQL cannot have the ORDERED BY in the count query.
    $query->entityOrderBy('entity_id');

    $limit = 20; //variable_get('xmlsitemap_batch_limit', 100)
    $query->range(0, $limit);

    $result = $query->execute();
    $ids = array_keys($result[$entity]);

    $info['xmlsitemap']['process callback']($ids);
    $context['sandbox']['last_id'] = end($ids);
    $context['sandbox']['progress'] += count($ids);
    $context['message'] = t('Now processing %entity @last_id (@progress of @count).', array('%entity' => $entity, '@last_id' => $context['sandbox']['last_id'], '@progress' => $context['sandbox']['progress'], '@count' => $context['sandbox']['max']));

    if ($context['sandbox']['progress'] >= $context['sandbox']['max']) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildBatchFinished($success, $results, $operations, $elapsed) {
    if ($success && !variable_get('xmlsitemap_rebuild_needed', FALSE)) {
      drupal_set_message(t('The sitemap links were rebuilt.'));
    }
    else {
      drupal_set_message(t('The sitemap links were not successfully rebuilt.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRebuildableLinkTypes() {
    $rebuild_types = array();
    $entities = xmlsitemap_get_link_info();

    foreach ($entities as $entity => $info) {
      if (empty($info['xmlsitemap']['rebuild callback'])) {
        // If the entity is missing a rebuild callback, skip.
        continue;
      }
      if (!empty($info['entity keys']['bundle']) && !xmlsitemap_get_link_type_enabled_bundles($entity)) {
        // If the entity has bundles, but no enabled bundles, skip since
        // rebuilding wouldn't get any links.
        continue;
      }
      else {
        $rebuild_types[] = $entity;
      }
    }

    return $rebuild_types;
  }

}
