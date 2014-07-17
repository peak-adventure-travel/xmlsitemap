<?php

/**
 * @file
 * Administrative page callbacks for the xmlsitemap module.
 *
 * @ingroup xmlsitemap
 */

/**
 * Add a table summary for an entity and its bundles.
 */
function xmlsitemap_add_form_entity_summary(&$form, $entity, array $entity_info) {
  $priorities = xmlsitemap_get_priority_options(NULL, FALSE);
  $statuses = xmlsitemap_get_status_options(NULL);
  $destination = drupal_get_destination();

  $rows = array();
  $totals = array('total' => 0, 'indexed' => 0, 'visible' => 0);
  foreach ($entity_info['bundles'] as $bundle => $bundle_info) {
    // Fetch current per-bundle link total and indexed counts.
    $status = xmlsitemap_get_link_type_indexed_status($entity, $bundle);
    $totals['total'] += $status['total'];
    $totals['indexed'] += $status['indexed'];
    $totals['visible'] += $status['visible'];

    $row = array();
    if (drupal_valid_path("admin/config/search/xmlsitemap/settings/$entity/$bundle")) {
      $edit_link = xmlsitemap_get_operation_link("admin/config/search/xmlsitemap/settings/$entity/$bundle", array('title' => $bundle_info['label'], 'modal' => TRUE));
      $row[] = l($edit_link['title'], $edit_link['href'], $edit_link);
    }
    else {
      // Bundle labels are assumed to be un-escaped input.
      $row[] = check_plain($bundle_info['label']);
    }
    $row[] = $statuses[$bundle_info['xmlsitemap']['status'] ? 1 : 0];
    $row[] = $priorities[number_format($bundle_info['xmlsitemap']['priority'], 1)];
    $row[] = $status['total'];
    $row[] = $status['indexed'];
    $row[] = $status['visible'];
    $rows[] = $row;
  }

  if ($rows) {
    $header = array(
      isset($entity_info['bundle label']) ? $entity_info['bundle label'] : '',
      t('Inclusion'),
      t('Priority'),
      t('Available'),
      t('Indexed'),
      t('Visible'),
    );
    $rows[] = array(
      array(
        'data' => t('Totals'),
        'colspan' => 3,
        'header' => TRUE,
      ),
      array(
        'data' => $totals['total'],
        'header' => TRUE,
      ),
      array(
        'data' => $totals['indexed'],
        'header' => TRUE,
      ),
      array(
        'data' => $totals['visible'],
        'header' => TRUE,
      ),
    );
    $form['summary'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
  }
}

/**
 * Add the link type XML sitemap options to the link type's form.
 *
 * Caller is responsible for ensuring xmlsitemap_link_bundle_settings_save()
 * is called during submission.
 */
function xmlsitemap_add_link_bundle_settings(array &$form, array &$form_state, $entity, $bundle) {
  $entity_info = xmlsitemap_get_link_info($entity);

  //if (!isset($bundle) && isset($entity_info['bundle keys']['bundle'])) {
  //  $bundle_key = $entity_info['bundle keys']['bundle'];
  //  if (isset($form[$bundle_key]['#value'])) {
  //    $bundle = $form[$bundle_key]['#value'];
  //  }
  //  elseif (isset($form[$bundle_key]['#default_value'])) {
  //    $bundle = $form[$bundle_key]['#default_value'];
  //  }
  //}

  $bundle_info = xmlsitemap_link_bundle_load($entity, $bundle);

  $form['xmlsitemap'] = array(
    '#type' => 'details',
    '#title' => t('XML sitemap'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#access' => \Drupal::currentUser()->hasPermission('administer xmlsitemap'),
    '#group' => 'advanced',
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'xmlsitemap') . '/js/xmlsitemap.js',
      ),
    ),
    '#tree' => TRUE,
    '#entity' => $entity,
    '#bundle' => $bundle,
    '#entity_info' => $entity_info,
    '#bundle_info' => $bundle_info,
  );

  // Hack to remove fieldset summary if Vertical tabs is not enabled.
  if (!isset($form['advanced'])) {
    unset($form['xmlsitemap']['#attached']['js']['vertical-tabs']);
  }

  $form['xmlsitemap']['description'] = array(
    '#prefix' => '<div class="description">',
    '#suffix' => '</div>',
    '#markup' => t('Changing these type settings will affect any items of this type that have either inclusion or priority set to default.'),
  );
  $form['xmlsitemap']['status'] = array(
    '#type' => 'select',
    '#title' => t('Inclusion'),
    '#options' => xmlsitemap_get_status_options(),
    '#default_value' => $bundle_info['status'],
  );
  $form['xmlsitemap']['priority'] = array(
    '#type' => 'select',
    '#title' => t('Default priority'),
    '#options' => xmlsitemap_get_priority_options(),
    '#default_value' => $bundle_info['priority'],
    '#states' => array(
      'invisible' => array(
        'select[name="xmlsitemap[status]"]' => array('value' => '0'),
      ),
    ),
  );
}

/**
 * Get a list of priority options.
 *
 * @param $default
 *   Include a 'default' option.
 * @param $guides
 *   Add helpful indicators for the highest, middle and lowest values.
 * @return
 *   An array of options.
 */
function xmlsitemap_get_priority_options($default = NULL, $guides = TRUE) {
  $options = array();
  $priorities = array(
    '1.0' => t('1.0'),
    '0.9' => t('0.9'),
    '0.8' => t('0.8'),
    '0.7' => t('0.7'),
    '0.6' => t('0.6'),
    '0.5' => t('0.5'),
    '0.4' => t('0.4'),
    '0.3' => t('0.3'),
    '0.2' => t('0.2'),
    '0.1' => t('0.1'),
    '0.0' => t('0.0'),
  );

  if (isset($default)) {
    $default = number_format($default, 1);
    $options['default'] = t('Default (@value)', array('@value' => $priorities[$default]));
  }

  // Add the rest of the options.
  $options += $priorities;

  if ($guides) {
    $options['1.0'] .= ' ' . t('(highest)');
    $options['0.5'] .= ' ' . t('(normal)');
    $options['0.0'] .= ' ' . t('(lowest)');
  }

  return $options;
}

/**
 * Get a list of priority options.
 *
 * @param $default
 *   Include a 'default' option.
 * @return
 *   An array of options.
 *
 * @see _xmlsitemap_translation_strings()
 */
function xmlsitemap_get_status_options($default = NULL) {
  $options = array();
  $statuses = array(
    1 => t('Included'),
    0 => t('Excluded'),
  );

  if (isset($default)) {
    $default = $default ? 1 : 0;
    $options['default'] = t('Default (@value)', array('@value' => drupal_strtolower($statuses[$default])));
  }

  $options += $statuses;

  return $options;
}
