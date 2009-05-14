<?php
// $Id$

/**
 * @file
 * XML sitemap settings UI.
 */

/**
 * @addtogroup xmlsitemap
 * @{
 */

/*****************************************************************************
 * Menu callbacks / form builders, submit/validate functions.
 ****************************************************************************/

/**
 * Form builder; return the additional links form.
 */
function xmlsitemap_additional_links() {
  $links = _xmlsitemap_additional_links_list();
  $form = array('#tree' => TRUE);
  foreach ($links as $id => $link) {
    $form[$id]['#link'] = (array)$link;
    $form[$id]['path'] = array(
      '#value' => check_plain(truncate_utf8($link->path, 40, FALSE, TRUE))
    );
    $form[$id]['weight'] = array(
      '#type' => 'weight',
      '#delta' => 10,
      '#default_value' => $link->weight
    );
    $form[$id]['edit'] = array(
      '#value' => l(t('edit'), "admin/settings/xmlsitemap/additional/edit/{$id}")
    );
    $form[$id]['delete'] = array(
      '#value' => l(t('delete'), "admin/settings/xmlsitemap/additional/delete/{$id}")
    );
  }
  if (count($links) > 1) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save')
    );
  }
  elseif (isset($link)) {
    unset($form[$link->lid]['weight']);
  }
  return $form;
}

/**
 * Submit function for the additional links list.
 */
function xmlsitemap_additional_links_submit($form, &$form_state) {
  foreach ($form_state['values'] as $id => $link) {
    if (is_numeric($id) && $form[$id]['#link']['weight'] != $form_state['values'][$id]['weight']) {
      $form[$id]['#link']['weight'] = $form_state['values'][$id]['weight'];
      drupal_write_record('xmlsitemap_additional', $form[$id]['#link'], 'lid');
    }
  }
}

/**
 * Form builder; display the form to edit the additional links.
 */
function xmlsitemap_additional_links_edit($form_state, $link = NULL) {
  if (!isset($link)) {
    $link = (object) array(
      'path' => '',
      'weight' => 0,
    );
  }
  else {
    $form['#lid'] = $link->lid;
  }
  $form['path'] = array(
    '#type' => 'textfield',
    '#title' => t('Relative path'),
    '#description' => t('The path must be relative to the Drupal installation directory, and accessible from the anonymous user. If the path is a path alias, the original path will be used.'),
    '#default_value' => $link->path,
    '#size' => 40,
    '#maxlength' => 128,
    '#required' => TRUE,
  );
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $link->weight,
    '#delta' => 10,
    '#description' => t('The weight of a link determinates its sitemap priority; the links with a heavier weight will have a lower priority'),
  );
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 10,
  );
  return $form;
}

/**
 * Validate function for the edit form.
 */
function xmlsitemap_additional_links_edit_validate($form, &$form_state) {
  global $user;
  $path = drupal_get_normal_path($form_state['values']['path']);
  // Set the current user to the anonymous user to verify the links being
  // added are accessible from the anonymous user.
  session_save_session(FALSE);
  $original_user = $user;
  $user = drupal_anonymous_user();
  $menu = menu_get_item($path);
  // Set the current user to the original value.
  $user = $original_user;
  session_save_session(TRUE);
  if (!$menu) {
    form_set_error('path', t('The path is not valid.'));
    return;
  }
  if (!isset($menu['access']) || !$menu['access']) {
    form_set_error('path', t('The path is not accessible from the anonymous user.'));
    return;
  }
  $lid = db_result(db_query("SELECT lid from {xmlsitemap_additional} WHERE path = '%s'", $path));
  if ($lid !== FALSE) {
    $error = t('The path has been already used.');
    if (isset($form['#lid'])) {
      if ($form['#lid'] != $lid) {
        form_set_error('path', $error);
      }
    }
    else {
      form_set_error('path', $error);
    }
  }
  else {
    form_set_value($form['path'], $path, $form_state);
  }
}

/**
 * Submit function for the edit form.
 */
function xmlsitemap_additional_links_edit_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  if (isset($form['#lid'])) {
    $form_values['lid'] = $form['#lid'];
    drupal_write_record('xmlsitemap_additional', $form_values, 'lid');
    drupal_set_message(t('The %link link has been updated.', array('%link' => $form_values['path'])));
    watchdog('xmlsitemap', 'The %link link has been updated.', array('%link' => $form_values['path']));
  }
  else {
    drupal_write_record('xmlsitemap_additional', $form_values);
    drupal_set_message(t('The %link link has been created.', array('%link' => $form_values['path'])));
    watchdog('xmlsitemap', 'The %link link has been created.', array('%link' => $form_values['path']));
  }
  
  $form_state['redirect'] = 'admin/settings/xmlsitemap/additional/list';
}

/**
 * Form builder; display the form to delete an additional link.
 */
function xmlsitemap_additional_links_delete_confirm($form_state, $lid) {
  $form['#lid'] = $lid;
  
  return confirm_form($form,
    t('Are you sure you want to delete the link?'),
    isset($_GET['destination']) ? $_GET['destination'] : 'admin/settings/xmlsitemap/additional/list',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Submission function for the delete form.
 */
function xmlsitemap_additional_links_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    db_query('DELETE FROM {xmlsitemap_additional} WHERE lid = %d', $form['#lid']);
    drupal_set_message(t('The link has been deleted'), 'status', FALSE);
  }

  $form_state['redirect'] = 'admin/settings/xmlsitemap/additional/list';
}

/**
 * Form builder; return the sitemap settings form.
 */
function xmlsitemap_settings() {
  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
    '#collapsible' => TRUE,
    '#weight' => -1,
  );
  $form['general']['xmlsitemap_all_links_to_default_language'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add all the links to the default language sitemap'),
    '#default_value' => variable_get('xmlsitemap_all_links_to_default_language', 0),
    '#description' => t('This option will be used only when the language negotiation uses a path prefix mechanism.'),
  );
  $form['general']['xmlsitemap_cron_limit'] = array(
    '#type' => 'select',
    '#title' => t('Cron limit'),
    '#description' => t('The number of links that are updated in each pass of a <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    '#default_value' => variable_get('xmlsitemap_cron_limit', 100),
    '#options' => xmlsitemap_cron_options(),
  );
  $form['general']['xmlsitemap_cache_directory'] = array(
    '#type' => 'textfield',
    '#title' => t('Cache directory'),
    '#default_value' => variable_get('xmlsitemap_cache_directory', file_directory_path() .'/xmlsitemap'),
    '#size' => 60,
    '#maxlength' => 150,
    '#description' => t('The directory where the cache files are created; change it only if you are having problems with the default setting.'),
  );
  $form['frontpage'] = array(
    '#type' => 'fieldset',
    '#title' => t('Front page'),
    '#collapsible' => TRUE,
  );
  $form['frontpage']['xmlsitemap_front_page_changefreq'] = array(
    '#type' => 'select',
    '#title' => t('Front page change frequency'),
    '#description' => t('The change frequency associated with the front page.'),
    '#default_value' => variable_get('xmlsitemap_front_page_changefreq', 3600),
    '#options' => array(
      '3600' => 'Hourly',
      '86400' => 'Daily',
      '604800' => 'Weekly',
      '2419200' => 'Monthly',
      '29030400' => 'Yearly',
    ),
  );
  $form['frontpage']['xmlsitemap_front_page_priority'] = array(
    '#type' => 'select',
    '#title' => t('Front page priority'),
    '#description' => t('The absolute priority for the front page.'),
    '#default_value' => variable_get('xmlsitemap_front_page_priority', 1),
    '#options' => xmlsitemap_priority_options(),
  );
  $form = system_settings_form($form);
  $form['buttons']['#weight'] = 10;
  $form['#submit'][] = 'xmlsitemap_settings_submit';
  $form['#validate'][] = 'xmlsitemap_settings_validate';
  return $form;
}

/**
 * Validate the sitemap settings form.
 */
function xmlsitemap_settings_validate($form, &$form_state) {
  $directory = $form_state['values']['xmlsitemap_cache_directory'];
  file_check_directory($directory, FILE_CREATE_DIRECTORY, 'xmlsitemap_cache_directory');
  $form_state['values']['xmlsitemap_cache_directory'] = $directory;
}

/**
 * Submit the settings form.
 */
function xmlsitemap_settings_submit($form, &$form_state) {
  xmlsitemap_flag_sitemap();
}

/**
 * Form builder; return the tools form.
 */
function xmlsitemap_tools_basic() {
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Operations'),
    '#collapsible' => FALSE, 
    '#collapsed' => FALSE,
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $options = array();
  foreach (module_invoke_all('xmlsitemap_operations') as $operation => $info) {
    $options[$operation] = $info['label'];
  }
  reset($options);
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => key($options),
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Apply'),
    '#submit' => array('xmlsitemap_tools_basic_submit'),
  );
  return $form;
}

/**
 * Submit the tools form.
 */
function xmlsitemap_tools_basic_submit($form, &$form_state) {
  $operations = module_invoke_all('xmlsitemap_operations');
  $operation = $operations[$form_state['values']['operation']];
  $function = $operation['callback'];
  if (isset($operation['callback arguments'])) {
    call_user_func_array($function, $operation['callback arguments']);
  }
  else {
    call_user_func($function, NULL);
  }
  $form_state['redirect'] = 'admin/settings/xmlsitemap/tools';
}

/**
 * Form builder; return the tools settings form.
 */
function xmlsitemap_tools_settings() {
  $form['empty'] = array(
    '#type' => 'fieldset',
  );
  $form['empty']['info'] = array(
    '#value' => t('There are no settings currently defined.'),
    '#prefix' => '<div>',
    '#suffix' => '</div>',
  );
  return $form;
}

/*****************************************************************************
 * Theme functions.
 ****************************************************************************/

function theme_xmlsitemap_additional_links($form) {
  $rows = array();
  foreach (element_children($form) as $key) {
    if (isset($form[$key]['path'])) {
      $link = &$form[$key];
      
      $row = array();
      $row[] = drupal_render($link['path']);
      if (isset($link['weight'])) {
        $link['weight']['#attributes']['class'] = 'link-weight';
        $row[] = drupal_render($link['weight']);
      }
      $row[] = drupal_render($link['edit']);
      $row[] = drupal_render($link['delete']);
      
      $rows[] = array(
        'data' => $row,
        'class' => 'draggable'
      );
    }
  }
  if (empty($rows)) {
    $rows[] = array(
      array(
        'data' => t('No additional links have been created.'),
        'colspan' => '3'
      )
    );
  }
  $header = array(t('Path'));
  if (isset($form['submit'])) {
    $header[] = t('Weight');
    drupal_add_tabledrag('xmlsitemap-additional', 'order', 'sibling', 'link-weight');
  }
  $header[] = array('data' => t('Operations'), 'colspan' => '2');
  return theme('table', $header, $rows, array('id' => 'xmlsitemap-additional')) . drupal_render($form);
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
