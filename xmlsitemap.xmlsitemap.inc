<?php

/**
 * @file
 * XML sitemap integration functions for xmlsitemap.module.
 */
use Drupal\xmlsitemap\XmlSitemapInterface;

/**
 * Implements hook_xmlsitemap_link_info().
 */
function xmlsitemap_xmlsitemap_link_info() {
  return array(
    'frontpage' => array(
      'label' => t('Frontpage'),
      'xmlsitemap' => array(
        'settings callback' => 'xmlsitemap_link_frontpage_settings',
      ),
    ),
  );
}

/**
 * XML sitemap link type settings callback for frontpage link entity.
 */
function xmlsitemap_link_frontpage_settings(&$form) {
  module_load_include('admin.inc', 'xmlsitemap');
  if (user_access('administer site configuration')) {
    $form['#description'] = t('The front page path can be changed in the <a href="@url-frontpage">site information configuration</a>.', array('@url-frontpage' => url('admin/config/system/site-information')));
  }
  $form['xmlsitemap_frontpage_priority'] = array(
    '#type' => 'select',
    '#title' => t('Priority'),
    '#options' => xmlsitemap_get_priority_options(),
    '#default_value' => \Drupal::config('xmlsitemap.settings')->get('frontpage_priority'),
  );
  $form['xmlsitemap_frontpage_changefreq'] = array(
    '#type' => 'select',
    '#title' => t('Change frequency'),
    '#options' => xmlsitemap_get_changefreq_options(),
    '#default_value' => \Drupal::config('xmlsitemap.settings')->get('frontpage_changefreq'),
  );
  return $form;
}

/**
 * Implements hook_xmlsitemap_link_alter().
 */
function xmlsitemap_xmlsitemap_link_alter(&$link) {
  // Alter the frontpage priority.
  if ($link['type'] == 'frontpage' || $link['loc'] == '' || $link['loc'] == Drupal::config('system.site')->get('page.front')) {
    $link['priority'] = \Drupal::config('xmlsitemap.settings')->get('frontpage_priority');
    $link['changefreq'] = \Drupal::config('xmlsitemap.settings')->get('frontpage_changefreq');
  }
}

/**
 * Implements hook_xmlsitemap_links().
 */
function xmlsitemap_xmlsitemap_links() {
  // Frontpage link.
  $links[] = array(
    'type' => 'frontpage',
    'id' => 0,
    'loc' => '',
  );
  return $links;
}

/**
 * Implements hook_xmlsitemap_sitemap_operations().
 */
function xmlsitemap_xmlsitemap_sitemap_operations() {
  $operations['update'] = array(
    'label' => t('Update cached files'),
    'action past' => t('Updated'),
    'callback' => 'xmlsitemap_sitemap_multiple_update',
  );
  return $operations;
}

/**
 * XML sitemap operation callback; regenerate sitemap files using the batch API.
 *
 * @param $smids
 *   An array of XML sitemap IDs.
 *
 * @see xmlsitemap_regenerate_batch()
 */
function xmlsitemap_sitemap_multiple_update(array $smids) {
  module_load_include('generate.inc', 'xmlsitemap');
  $batch = xmlsitemap_regenerate_batch($smids);
  batch_set($batch);
}

/**
 * Implements hook_query_TAG_alter().
 */
function xmlsitemap_query_xmlsitemap_link_bundle_access_alter(QueryAlterableInterface $query) {
  if ($query instanceof EntityFieldQuery && $entity = $query->getMetaData('entity')) {
    $info = $query->getMetaData('entity_info');
    $bundle = $query->getMetaData('bundle');
    if (empty($bundle)) {
      $bundle = xmlsitemap_get_link_type_enabled_bundles($entity);
    }
    $query->entityCondition('bundle', $bundle, is_array($bundle) ? 'IN' : '=');
  }
}
