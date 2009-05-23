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
  $form = array('#tree' => TRUE);
  $links = _xmlsitemap_additional_links_list();
  foreach ($links as $id => $link) {
    $form[$id]['#link'] = (array)$link;
    $form[$id]['pdata'] = array(
      '#value' => check_plain(truncate_utf8($link->pdata, 40, FALSE, TRUE))
    );
    $form[$id]['status'] = array(
      '#type' => 'checkbox',
      '#default_value' => $link->status,
    );
    $form[$id]['priority'] = array(
      '#type' => 'select',
      '#default_value' => $link->priority,
      '#options' => xmlsitemap_priority_options(),
    );
    $form[$id]['edit'] = array(
      '#value' => l(t('edit'), "admin/settings/xmlsitemap/additional/edit/{$id}")
    );
    $form[$id]['delete'] = array(
      '#value' => l(t('delete'), "admin/settings/xmlsitemap/additional/delete/{$id}")
    );
  }
  if (count($links)) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save')
    );
  }
  return $form;
}

/**
 * Submit function for the additional links list.
 */
function xmlsitemap_additional_links_submit($form, &$form_state) {
  $saved = TRUE;
  foreach ($form_state['values'] as $id => $link) {
    if (is_numeric($id) && ($form[$id]['#link']['status'] != $form_state['values'][$id]['status'] || $form[$id]['#link']['priority'] != $form_state['values'][$id]['priority'])) {
      $form[$id]['#link']['status'] = $form_state['values'][$id]['status'];
      $form[$id]['#link']['priority'] = $form_state['values'][$id]['priority'];
      $row = (object)(array() + $form[$id]['#link']);
      $row->changed = REQUEST_TIME;
      $saved = $saved && xmlsitemap_write_link($row, 'xmlsitemap_additional', 'xmlsitemap');
    }
  }
  if (!$saved) {
    drupal_set_message(t('An error occurred while saving the links.'), 'error', FALSE);
  }
  else {
    drupal_set_message(t('The links have been saved.'), 'status', FALSE);
  }
}

/**
 * Form builder; display the form to edit the additional links.
 */
function xmlsitemap_additional_links_edit($form_state, $link = NULL) {
  if (!isset($link)) {
    $link = (object) array(
      'pdata' => '',
      'status' => TRUE,
      'priority' => 0.5,
    );
  }
  else {
    $form['#mid'] = $link->mid;
  }
  $form['pdata'] = array(
    '#type' => 'textfield',
    '#title' => t('Relative path'),
    '#description' => t('The path must be relative to the Drupal installation directory, and accessible from the anonymous user. If the path is a path alias, the original path will be used.'),
    '#default_value' => $link->pdata,
    '#size' => 40,
    '#maxlength' => 128,
    '#required' => TRUE,
  );
  $form['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => $link->status,
    '#description' => t('If selected, the link will appear in the sitemap.'),
  );
  $form['priority'] = array(
    '#type' => 'select',
    '#title' => t('Priority'),
    '#default_value' => $link->priority,
    '#options' => xmlsitemap_priority_options(TRUE),
    '#description' => t('The priority of a link determinates its position in the sitemap; the links with a higher priority will appear first in the sitemap.'),
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
  if (empty($menu['access'])) {
    form_set_error('path', t('The path is not accessible from the anonymous user.'));
    return;
  }
  $mid = db_result(db_query("SELECT mid from {xmlsitemap_additional} WHERE pdata = '%s'", $path));
  if ($mid !== FALSE) {
    $error = t('The path has been already used.');
    if (isset($form['#mid'])) {
      if ($form['#mid'] != $mid) {
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
  $form_state['values']['mid'] = $form['#mid'];
  $link = (object) (array() + $form_state['values']);
  if ($rc = xmlsitemap_write_link($link, 'xmlsitemap_additional', 'xmlsitemap')) {
    drupal_set_message(t('The link %link has been updated.', array('%link' => $link->pdata)), 'status', FALSE);
    watchdog('xmlsitemap', 'The link %link has been updated.', array('%link' => $link->pdata));
  }
  if (!rc) {
    drupal_set_message(t('An error occurred while saving the link %link.', array('%link' => $link->pdata)), 'error', FALSE);
    watchdog('xmlsitemap', 'An error occurred while saving the link %link.', array('%link' => $link->pdata), WATCHDOG_ERROR);
  }
  $form_state['redirect'] = 'admin/settings/xmlsitemap/additional/list';
}

/**
 * Form builder; display the form to delete an additional link.
 */
function xmlsitemap_additional_links_delete_confirm($form_state, $link) {
  $form['#link'] = $link;
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
    $link = $form['#link'];
    db_query('DELETE FROM {xmlsitemap_additional} WHERE mid = %d', $link->mid);
    drupal_set_message(t(''The link %link has been deleted.', array('%link' => $link->pdata)), 'status', FALSE);
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
    '#description' => t('The number of links that are updated in each pass of a <a href="@cron">cron maintenance task</a>. Choosing a number too high could cause the other module cron tasks to not be executed; use the higher numbers only when you are sure it will not cause any problems on your Drupal installation.', array('@cron' => url('admin/reports/status'))),
    '#default_value' => variable_get('xmlsitemap_cron_limit', 200),
    '#options' => drupal_map_assoc(array(2, 10, 50, 100, 200, 300, 400, 500, 1000, 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000)),
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
      '3600' => t('Hourly'),
      '86400' => t('Daily'),
      '604800' => t('Weekly'),
      '2419200' => t('Monthly'),
      '29030400' => t('Yearly'),
    ),
  );
  $form['frontpage']['xmlsitemap_front_page_priority'] = array(
    '#type' => 'select',
    '#title' => t('Front page priority'),
    '#description' => t('The absolute priority for the front page.'),
    '#default_value' => variable_get('xmlsitemap_front_page_priority', 1.0),
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
  xmlsitemap_flag_sitemap(TRUE);
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
    if (isset($form[$key]['pdata'])) {
      $link = &$form[$key];
      
      $row = array();
      $row[] = drupal_render($link['pdata']);
      $row[] = drupal_render($link['status']);
      $row[] = drupal_render($link['priority']);
      $row[] = drupal_render($link['edit']);
      $row[] = drupal_render($link['delete']);
      
      $rows[] = array(
        'data' => $row,
      );
    }
  }
  if (empty($rows)) {
    $rows[] = array(
      array(
        'data' => t('No additional links have been created.'),
        'colspan' => '5'
      )
    );
  }
  $header[] = t('Path');
  $header[] = t('Enabled');
  $header[] = t('Priority');
  $header[] = array('data' => t('Operations'), 'colspan' => '2');
  return theme('table', $header, $rows) . drupal_render($form);
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
