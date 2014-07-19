<?php

/**
 * @file
 * Sitemap generation and rebuilding functions for the xmlsitemap module.
 *
 * @ingroup xmlsitemap
 */
use Drupal\Core\Language\LanguageInterface;
use Drupal\xmlsitemap\XmlSitemapInterface;
use Drupal\xmlsitemap\Entity\XmlSitemap;
use Drupal\xmlsitemap\XmlSitemapGenerationException;
use Drupal\xmlsitemap\XmlSitemapWriter;
use Drupal\Component\Utility\Bytes;

/**
 * Given an internal Drupal path, return the alias for the path.
 *
 * This is similar to drupal_get_path_alias(), but designed to fetch all alises
 * at once so that only one database query is executed instead of several or
 * possibly thousands during sitemap generation.
 *
 * @param $path
 *   An internal Drupal path.
 * @param $language
 *   A language code to use when looking up the paths.
 */
function xmlsitemap_get_path_alias($path, $language) {
  static $aliases;
  static $last_language;

  $query = db_select('url_alias', 'u');
  $query->fields('u', array('source', 'alias'));
  if (!isset($aliases)) {
    $query->condition('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED, '=');
    $aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED] = $query->execute()->fetchAllKeyed();
  }
  if ($language != LanguageInterface::LANGCODE_NOT_SPECIFIED && $last_language != $language) {
    unset($aliases[$last_language]);
    $query->condition('langcode', $language, '=');
    $query->orderBy('pid');
    $aliases[$language] = $query->execute()->fetchAllKeyed();
    $last_language = $language;
  }

  if ($language != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($aliases[$language][$path])) {
    return $aliases[$language][$path];
  }
  elseif (isset($aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED][$path])) {
    return $aliases[LanguageInterface::LANGCODE_NOT_SPECIFIED][$path];
  }
  else {
    return $path;
  }
}

/**
 * Perform operations before rebuilding the sitemap.
 */
function _xmlsitemap_regenerate_before() {
  // Attempt to increase the memory limit.
  _xmlsitemap_set_memory_limit();

  if (\Drupal::state()->get('developer_mode')) {
    watchdog('xmlsitemap', 'Starting XML sitemap generation. Memory usage: @memory-peak.', array(
      '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
        ), WATCHDOG_DEBUG
    );
  }
}

function _xmlsitemap_get_memory_usage($start = FALSE) {
  static $memory_start;
  $current = memory_get_peak_usage(TRUE);
  if (!isset($memory_start) || $start) {
    $memory_start = $current;
  }
  return $current - $memory_start;
}

/**
 * Calculate the optimal PHP memory limit for sitemap generation.
 *
 * This function just makes a guess. It does not take into account
 * the currently loaded modules.
 */
function _xmlsitemap_get_optimal_memory_limit() {
  $optimal_limit = &drupal_static(__FUNCTION__);
  if (!isset($optimal_limit)) {
    // Set the base memory amount from the provided core constant.
    $optimal_limit = Bytes::toInt(DRUPAL_MINIMUM_PHP_MEMORY_LIMIT);

    // Add memory based on the chunk size.
    $optimal_limit += xmlsitemap_get_chunk_size() * 500;

    // Add memory for storing the url aliases.
    if (\Drupal::config('xmlsitemap.settings')->get('prefetch_aliases', 1)) {
      $aliases = db_query("SELECT COUNT(pid) FROM {url_alias}")->fetchField();
      $optimal_limit += $aliases * 250;
    }
  }
  return $optimal_limit;
}

/**
 * Calculate the optimal memory level for sitemap generation.
 *
 * @param $new_limit
 *   An optional PHP memory limit in bytes. If not provided, the value of
 *   _xmlsitemap_get_optimal_memory_limit() will be used.
 */
function _xmlsitemap_set_memory_limit($new_limit = NULL) {
  $current_limit = @ini_get('memory_limit');
  if ($current_limit && $current_limit != -1) {
    if (!is_null($new_limit)) {
      $new_limit = _xmlsitemap_get_optimal_memory_limit();
    }
    if (Bytes::toInt($current_limit) < $new_limit) {
      return @ini_set('memory_limit', $new_limit);
    }
  }
}

/**
 * Generate one page (chunk) of the sitemap.
 *
 * @param $sitemap
 *   An unserialized data array for an XML sitemap.
 * @param $page
 *   An integer of the specific page of the sitemap to generate.
 */
function xmlsitemap_generate_page(XmlSitemapInterface $sitemap, $page) {
  try {
    $writer = new XmlSitemapWriter($sitemap, $page);
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

function xmlsitemap_generate_chunk(XmlSitemapInterface $sitemap, XmlSitemapWriter $writer, $chunk) {
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
      $link['loc'] = xmlsitemap_get_path_alias($link['loc'], $link['language']->id);
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
 * Generate the index sitemap.
 *
 * @param $sitemap
 *   An unserialized data array for an XML sitemap.
 */
function xmlsitemap_generate_index(XmlSitemapInterface $sitemap) {
  try {
    $writer = new XmlSitemapIndexWriter($sitemap);
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

// BATCH OPERATIONS ------------------------------------------------------------

/**
 * Batch information callback for regenerating the sitemap files.
 *
 * @param $smids
 *   An optional array of XML sitemap IDs. If not provided, it will load all
 *   existing XML sitemaps.
 */
function xmlsitemap_regenerate_batch(array $smids = array()) {
  if (empty($smids)) {
    $sitemaps = \Drupal::entityManager()->getStorage('xmlsitemap')->loadMultiple();
    foreach ($sitemaps as $sitemap) {
      $smids[] = $sitemap->id();
    }
  }

  $t = 't';
  $batch = array(
    'operations' => array(),
    'error_message' => $t('An error has occurred.'),
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
 * Batch callback; generate all pages of a sitemap.
 */
function xmlsitemap_regenerate_batch_generate($smid, array &$context) {
  if (!isset($context['sandbox']['sitemap'])) {
    //$sitemap = \Drupal::entityManager()->getStorage('xmlsitemap')->load($smid);
    $sitemap = xmlsitemap_sitemap_load($smid);
    $context['sandbox']['sitemap'] = $sitemap;
    $context['sandbox']['sitemap']->setChunks(1);
    $context['sandbox']['sitemap']->setLinks(0);
    $context['sandbox']['max'] = XMLSITEMAP_MAX_SITEMAP_LINKS;

    // Clear the cache directory for this sitemap before generating any files.
    xmlsitemap_check_directory($context['sandbox']['sitemap']);
    xmlsitemap_clear_directory($context['sandbox']['sitemap']);
  }
  $sitemap = &$context['sandbox']['sitemap'];
  $links = xmlsitemap_generate_page($sitemap, $sitemap->getChunks());
  $context['message'] = t('Now generating %sitemap-url.', array('%sitemap-url' => url('sitemap.xml', $sitemap->uri['options'] + array('query' => array('page' => $sitemap->getChunks())))));

  if ($links) {
    $sitemap->setLinks($sitemap->getLinks() + $links);
    $sitemap->setChunks($sitemap->getChunks() + 1);
  }
  else {
    // Cleanup the 'extra' empty file.
    $file = xmlsitemap_sitemap_get_file($sitemap, $sitemap->getChunks());
    if (file_exists($file) && $sitemap->getChunks() > 1) {
      file_unmanaged_delete($file);
    }
    $sitemap->setChunks($sitemap->getChunks() - 1);

    // Save the updated chunks and links values.
    $context['sandbox']['max'] = $sitemap->getChunks();
    $sitemap->setUpdated(REQUEST_TIME);
    xmlsitemap_sitemap_get_max_filesize($sitemap);
    xmlsitemap_sitemap_save($sitemap);
  }

  if ($sitemap->getChunks() != $context['sandbox']['max']) {
    $context['finished'] = $sitemap->getChunks() / $context['sandbox']['max'];
  }
}

/**
 * Batch callback; generate the index page of a sitemap.
 */
function xmlsitemap_regenerate_batch_generate_index($smid, array &$context) {
  $sitemap = xmlsitemap_sitemap_load($smid);
  if ($sitemap != NULL && $sitemap->getChunks() > 1) {
    xmlsitemap_generate_index($sitemap);
    $context['message'] = t('Now generating sitemap index %sitemap-url.', array('%sitemap-url' => url('sitemap.xml', $sitemap->uri['options'])));
  }
}

/**
 * Batch callback; sitemap regeneration finished.
 */
function xmlsitemap_regenerate_batch_finished($success, $results, $operations, $elapsed) {
  if ($success && \Drupal::state()->get('regenerate_needed') == FALSE) {
    \Drupal::state()->set('generated_last', REQUEST_TIME);
    drupal_set_message(t('The sitemaps were regenerated.'));
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
 * Batch information callback for rebuilding the sitemap data.
 */
function xmlsitemap_rebuild_batch(array $entities, $save_custom = FALSE) {
  $batch = array(
    'operations' => array(),
    'finished' => 'xmlsitemap_rebuild_batch_finished',
    'title' => t('Rebuilding Sitemap'),
    'file' => drupal_get_path('module', 'xmlsitemap') . '/xmlsitemap.generate.inc',
  );

  // Set the rebuild flag in case something fails during the rebuild.
  $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('rebuild_needed' => TRUE)));

  // Purge any links first.
  $batch['operations'][] = array('xmlsitemap_rebuild_batch_clear', array($entities, (bool) $save_custom));

  // Fetch all the sitemap links and save them to the {xmlsitemap} table.
  foreach ($entities as $entity) {
    $info = xmlsitemap_get_link_info($entity);
    $batch['operations'][] = array($info['xmlsitemap']['rebuild callback'], array($entity));
  }

  // Clear the rebuild flag.
  $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('rebuild_needed' => FALSE)));

  // Add the regeneration batch.
  $regenerate_batch = xmlsitemap_regenerate_batch();
  $batch['operations'] = array_merge($batch['operations'], $regenerate_batch['operations']);

  return $batch;
}

/**
 * Batch callback; set an array of variables and their values.
 */
function xmlsitemap_batch_variable_set(array $variables) {
  $state_variables = xmlsitemap_state_variables();
  $config_variables = xmlsitemap_config_variables();
  foreach ($variables as $variable => $value) {
    if (isset($state_variables[$variable])) {
      \Drupal::state()->set($variable, $value);
    }
    else {
      \Drupal::config('xmlsitemap.settings')->set($variable, $value);
    }
  }
  \Drupal::config('xmlsitemap.settings')->save();
}

/**
 * Batch callback; clear sitemap links for entites.
 */
function xmlsitemap_rebuild_batch_clear(array $entities, $save_custom, &$context) {
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
 * Batch callback; fetch and add the sitemap links for a specific entity.
 */
function xmlsitemap_rebuild_batch_fetch($entity, &$context) {
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

  // get batch limit
  $limit = \Drupal::config('xmlsitemap.settings')->get('batch_limit');
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
 * Batch callback; sitemap rebuild finished.
 */
function xmlsitemap_rebuild_batch_finished($success, $results, $operations, $elapsed) {
  if ($success && !\Drupal::state()->get('rebuild_needed')) {
    drupal_set_message(t('The sitemap links were rebuilt.'));
  }
  else {
    drupal_set_message(t('The sitemap links were not successfully rebuilt.'), 'error');
  }
}

function xmlsitemap_get_rebuildable_link_types() {
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
