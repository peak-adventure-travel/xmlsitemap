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
 * Perform operations before rebuilding the sitemap.
 */
function _xmlsitemap_regenerate_before() {
  \Drupal::service('xmlsitemap_generator')->regenerateBefore();
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
  $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('xmlsitemap_regenerate_needed' => TRUE)));

  // @todo Get rid of this batch operation.
  $batch['operations'][] = array('_xmlsitemap_regenerate_before', array());

  // Generate all the sitemap pages for each context.
  foreach ($smids as $smid) {
    $batch['operations'][] = array('xmlsitemap_regenerate_batch_generate', array($smid));
    $batch['operations'][] = array('xmlsitemap_regenerate_batch_generate_index', array($smid));
  }

  // Clear the regeneration flag.
  $batch['operations'][] = array('xmlsitemap_batch_variable_set', array(array('xmlsitemap_regenerate_needed' => FALSE)));

  return $batch;
}

/**
 * Batch callback; generate all pages of a sitemap.
 */
function xmlsitemap_regenerate_batch_generate($smid, array &$context = array()) {
  \Drupal::service('xmlsitemap_generator')->regenerateBatchGenerate($smid, $context);
}

/**
 * Batch callback; generate the index page of a sitemap.
 */
function xmlsitemap_regenerate_batch_generate_index($smid, array &$context = array()) {
  \Drupal::service('xmlsitemap_generator')->regenerateBatchGenerateIndex($smid, $context);
}

/**
 * Batch callback; sitemap regeneration finished.
 */
function xmlsitemap_regenerate_batch_finished($success, $results, $operations, $elapsed) {
  \Drupal::service('xmlsitemap_generator')->regenerateBatchFinished($success, $results, $operations, $elapsed);
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
 * Batch callback; set an array of variables and their values.
 */
function xmlsitemap_batch_variable_set(array $variables) {
  \Drupal::service('xmlsitemap_generator')->batchVariableSet($variables);
}

/**
 * Batch callback; clear sitemap links for entites.
 */
function xmlsitemap_rebuild_batch_clear(array $entities, $save_custom, &$context = array()) {
  \Drupal::service('xmlsitemap_generator')->rebuildBatchClear($entities, $save_custom, $context);
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
  \Drupal::service('xmlsitemap_generator')->rebuildBatchFinished($success, $results, $operations, $elapsed);
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
