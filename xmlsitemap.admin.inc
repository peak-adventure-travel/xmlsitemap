<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the xmlsitemap module.
 *
 * @ingroup xmlsitemap
 */

/**
 * Form builder; Administration settings form.
 *
 * @see system_settings_form()
 * @see xmlsitemap_settings_form_validate()
 */
function xmlsitemap_settings_form() {
  $form['xmlsitemap'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => -10,
  );
  $form['xmlsitemap']['xmlsitemap_minimum_lifetime'] = array(
    '#type' => 'select',
    '#title' => t('Minimum sitemap lifetime'),
    '#options' => array(0 => t('No minimum')) + drupal_map_assoc(array(300, 900, 1800, 3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800), 'format_interval'),
    '#default_value' => xmlsitemap_var('minimum_lifetime')
  );
  $form['xmlsitemap']['xmlsitemap_xsl'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include a stylesheet in the sitemaps.'),
    '#default_value' => xmlsitemap_var('xsl'),
    '#description' => t('Using the stylesheet will make it easier for non-computers to view and sort.')
  );
  $form['xmlsitemap']['xmlsitemap_gz'] = array(
    '#type' => 'checkbox',
    '#title' => t('Generate additional compressed sitemaps using gzip.'),
    '#default_value' => xmlsitemap_var('gz'),
    '#disabled' => !function_exists('gzencode'),
    '#access' => FALSE,
  );
  $form['xmlsitemap']['xmlsitemap_chunk_size'] = array(
    '#type' => 'select',
    '#title' => t('Number of links in each sitemap page'),
    '#options' => array('auto' => t('Automatic')) + drupal_map_assoc(array(100, 500, 1000, 2500, 5000, 10000, 25000, 50000)),
    '#default_value' => xmlsitemap_var('chunk_size'),
    // @todo This description is not clear.
    '#description' => t('If there are problems with rebuilding the sitemap, you may want to manually set this value. If you have more than 50,000 links, an index with multiple sitemap pages will be generated. There is a maximum of 1000 sitemap pages.'),
  );
  $form['xmlsitemap']['xmlsitemap_batch_limit'] = array(
    '#type' => 'select',
    '#title' => t('Maximum number of sitemap links to process at once'),
    '#options' => drupal_map_assoc(array(5, 10, 25, 50, 100, 250, 500, 1000, 2500)),
    '#default_value' => xmlsitemap_var('batch_limit'),
    '#description' => t('If you have problems running cron or rebuilding the sitemap, you may want to lower this value.'),
  );
  $form['xmlsitemap']['xmlsitemap_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Sitemap cache directory'),
    '#default_value' => xmlsitemap_var('path'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('Subdirectory in the directory %dir where the sitemap data will be stored. This folder <strong>must not be shared</strong> with any other Drupal site or install using XML sitemap.', array('%dir' => file_directory_path() .'/')),
    '#required' => TRUE,
  );
  $form['xmlsitemap']['xmlsitemap_base_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Base URL'),
    '#default_value' => xmlsitemap_var('base_url'),
    '#description' => t('This is the base URL for links generated in the sitemap.'),
    '#required' => TRUE,
  );
  $form['xmlsitemap']['xmlsitemap_languages'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Generate sitemaps for the following languages'),
    '#options' => function_exists('locale_language_list') ? locale_language_list() : array(language_default('language') => array(language_default('name'))),
    '#default_value' => xmlsitemap_var('languages'),
    '#access' => module_exists('locale'),
  );
  // Disable the default langage checkbox. It should always be enabled.
  $form['xmlsitemap']['xmlsitemap_languages'][language_default('language')] = array(
    '#type' => 'checkbox',
    '#title' => language_default('name'),
    '#return_value' => language_default('language'),
    '#default_value' => TRUE,
    '#disabled' => TRUE,
  );

  $form['frontpage'] = array(
    '#type' => 'fieldset',
    '#title' => t('Front page'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -1,
  );
  $form['frontpage']['xmlsitemap_frontpage_priority'] = array(
    '#type' => 'select',
    '#title' => t('Priority'),
    '#options' => xmlsitemap_get_priority_options(),
    '#default_value' => xmlsitemap_var('frontpage_priority'),
  );
  $form['frontpage']['xmlsitemap_frontpage_changefreq'] = array(
    '#type' => 'select',
    '#title' => t('Change frequency'),
    '#options' => /*array(0 => t('Automatic')) +*/ xmlsitemap_get_changefreq_options(),
    '#default_value' => xmlsitemap_var('frontpage_changefreq'),
  );

  //$form['advanced'] = array(
  //  '#type' => 'fieldset',
  //  '#title' => t('Advanced'),
  //  '#collapsible' => TRUE,
  //  '#collapsed' => TRUE,
  //  '#weight' => 10,
  //);

  $form['#validate'][] = 'xmlsitemap_settings_form_validate';
  $form['#submit'][] = 'xmlsitemap_form_submit_flag_regenerate';
  $form['#submit'][] = 'xmlsitemap_settings_form_submit';
  $form = system_settings_form($form);
  $form['buttons']['#weight'] = 20;
  $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
  return $form;
}

/**
 * Create a list of items that can be included in the sitemap.
 *
 * @param $item_type
 *   A translated string of the item type.
 * @param $items
 *   An array of items with the following keys:
 *     'name': The translated name of the item.
 *     'link': The path to edit the item.
 *     'status': The item's sitemap status.
 *     'priority': The items default sitemap priority.
 * @param $form_item
 *   The form item to add the list to.
 */
function xmlsitemap_settings_list($item_type, $items, &$form_item) {
  $header = array(
    $item_type,
    t('Included in sitemap?'),
    t('Default priority'),
  );
  $rows = array();

  foreach ($items as $item) {
    $rows[] = array(
      l($item['name'], $item['link'], array('query' => drupal_get_destination())),
      $item['status'] ? t('Included') : t('Not included'),
      $item['priority'] == 'default' ? t('Default') : t($item['priority']),
    );
  }

  $form_item['list'] = array(
    '#value' => theme('table', $header, $rows),
  );
}

/**
 * Submit handler; Set the regenerate needed flag if variables have changed.
 *
 * This function needs to be called before system_settings_form_submit() or any
 * calls to variable_set().
 */
function xmlsitemap_form_submit_flag_regenerate($form, $form_state) {
  foreach ($form_state['values'] as $variable => $value) {
    $stored_value = variable_get($variable, 'xmlsitemap_not_a_variable');
    if (is_array($value) && isset($form_state['values']['array_filter'])) {
      $value = array_keys(array_filter($value));
    }
    if ($stored_value != 'xmlsitemap_not_a_variable' && $stored_value != $value) {
      variable_set('xmlsitemap_regenerate_needed', TRUE);
      return;
    }
  }
}

/**
 * Form validator; Check the sitemap files directory.
 *
 * @see xmlsitemap_settings_form()
 */
function xmlsitemap_settings_form_validate($form, &$form_state) {
  // Check if the xmlsitemap files directory has changed.
  $path_new = file_create_path($form_state['values']['xmlsitemap_path']);
  $path_old = file_create_path(xmlsitemap_var('path'));
  if ($path_new != $path_old && file_check_directory($path_new, FILE_CREATE_DIRECTORY, 'xmlsitemap_path')) {
    // If creating the new directory was successful, remove the old directory.
    xmlsitemap_clear_cache(TRUE, $path_old);
  }

  $base_url = &$form_state['values']['xmlsitemap_base_url'];
  $base_url = rtrim($base_url, '/');
  if ($base_url != '' && !valid_url($base_url, TRUE)) {
    form_set_error('xmlsitemap_base_url', t('Invalid base URL.'));
  }
}

/**
 * Submit handler;
 *
 * @see xmlsitemap_settings_form()
 */
function xmlsitemap_settings_form_submit($form, $form_state) {
  // Save any changes to the frontpage link.
  $frontpage_link = array(
    'type' => 'frontpage',
    'id' => 0,
    'loc' => '',
  );
  xmlsitemap_save_link($frontpage_link);
}

/**
 * Menu callback; Confirm rebuilding of the sitemap.
 *
 * @see xmlsitemap_rebuild_confirm_submit()
 */
function xmlsitemap_rebuild_confirm() {
  $modules = module_implements('xmlsitemap_links', TRUE);
  $form['modules'] = array(
    '#type' => 'select',
    '#title' => t("Select which modules' links you would like to rebuild"),
    '#description' => t('If no modules are selected, the sitemap files will just be regenerated.'),
    '#multiple' => TRUE,
    '#options' => drupal_map_assoc($modules),
    '#default_value' => array(),
  );

  return confirm_form(
    $form,
    t('Are you sure you want to rebuild the sitemap?'),
    'admin/settings/xmlsitemap',
    t("This action rebuilds your site's sitemap, and may be a lengthy process. This will also recreate the sitemap files."),
    t('Rebuild sitemap'),
    t('Cancel')
  );
}

/**
 * Submit handler; Starts the sitemap rebuild batch.
 *
 * @see xmlsitemap_rebuild_confirm()
 * @see xmlsitemap_rebuild_batch()
 */
function xmlsitemap_rebuild_confirm_submit($form, &$form_state) {
  // Set the rebuild flag incase something fails during the rebuild.
  variable_set('xmlsitemap_rebuild_needed', TRUE);

  batch_set(xmlsitemap_rebuild_batch($form_state));
  $form_state['redirect'] = 'admin/settings/xmlsitemap';
}

/**
 * Batch information callback.
 */
function xmlsitemap_rebuild_batch($form_state) {
  $batch = array(
    'operations' => array(),
    'finished' => 'xmlsitemap_rebuild_batch_finished',
    'title' => t('Rebuilding Sitemap'),
    'file' => drupal_get_path('module', 'xmlsitemap') . '/xmlsitemap.inc',
  );

  // Fetch all the sitemap links and save them to the {xmlsitemap} table.
  foreach ($form_state['values']['modules'] as $module) {
    $batch['operations'][] = array('xmlsitemap_rebuild_batch_clear', array($module));
    $batch['operations'][] = array('xmlsitemap_rebuild_batch_fetch', array($module));
  }

  // Generate all the sitemap pages.
  $batch['operations'][] = array('_xmlsitemap_regenerate_before', array());
  $languages = language_list();
  foreach (xmlsitemap_var('languages') as $language) {
    $batch['operations'][] = array('xmlsitemap_rebuild_batch_generate', array($languages[$language]));
  }
  $batch['operations'][] = array('_xmlsitemap_regenerate_after', array());

  return $batch;
}