<?php
// $Id$

/**
 * @file
 * XML sitemap engines settings UI.
 */

/**
 * @addtogroup xmlsitemap
 * @{
 */

/*****************************************************************************
 * Menu callbacks / form builders, submit/validate functions.
 ****************************************************************************/

/**
 * Form builder; return the search engine settings form.
 */
function xmlsitemap_engines_settings() {
  $form['submission'] = array(
    '#type' => 'fieldset',
    '#title' => t('Submission settings'),
    '#collapsible' => TRUE,
  );
  $form['submission']['xmlsitemap_engines_submit'] = array(
    '#type' => 'checkbox',
    '#title' => t('Submit the sitemap when updated'),
    '#description' => t('If enabled, the search engines will be notified of changes to the sitemap each time it is updated.'),
    '#default_value' => variable_get('xmlsitemap_engines_submit', FALSE),
  );
  $form['submission']['xmlsitemap_engines_cron_submit_frequency'] = array(
    '#type' => 'select',
    '#title' => t('Frequency of sitemap submission'),
    '#description' => t('The rate at which the sitemap is submitted to the search engines.'),
    '#default_value' => variable_get('xmlsitemap_engines_cron_submit_frequency', 3600),
    '#options' => array(
      '29030400' => t('Yearly'),
      '14515200' => t('Every 6 months'),
      '2419200' => t('Monthly'),
      '1296000' => t('Every 15 days'),
      '604800' => t('Weekly'),
      '86400' => t('Daily'),
      '3600' => t('Hourly'),
      '-1' => t('Never'),
    ),
  );
  // Ask.com fields.
  $form['ask'] = array(
    '#type' => 'fieldset',
    '#title' => t('Ask.com'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _xmlsitemap_engines_add_submission_url_fields($form, 'ask', 'http://submissions.ask.com/ping?sitemap=[sitemap]');
  // Google fields.
  $form['google'] = array(
    '#type' => 'fieldset',
    '#title' => t('Google'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _xmlsitemap_engines_add_submission_url_fields($form, 'google', 'http://www.google.com/webmasters/tools/ping?sitemap=[sitemap]');
  $form['google']['xmlsitemap_engines_google_verify'] = array(
    '#type' => 'textfield',
    '#title' => t('Verification link'),
    '#description' => t('In order to show statistics, Google will ask you to verify that you control this site by creating a file with a certain name. Enter that name here and the XML sitemap module will create a path to that file name. This will only work if you have clean URLs enabled.'),
    '#default_value' => variable_get('xmlsitemap_engines_google_verify', ''),
  );
  // Moreover.com fields.
  $form['moreover'] = array(
    '#type' => 'fieldset',
    '#title' => t('Moreover.com'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _xmlsitemap_engines_add_submission_url_fields($form, 'moreover', 'http://api.moreover.com/ping?u=[sitemap]');
  // Windows Live fields.
  $form['live'] = array(
    '#type' => 'fieldset',
    '#title' => t('Windows Live'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _xmlsitemap_engines_add_submission_url_fields($form, 'live', 'http://webmaster.live.com/ping.aspx?siteMap=[sitemap]');
  $form['live']['xmlsitemap_engines_live_verify'] = array(
    '#type' => 'textfield',
    '#title' => t('Authentication file'),
    '#description' => t('Before allowing you to view additional information, Windows Live will ask you to verify that you control this site by creating a file with a certain name. Enter that name here, and XML sitemap will create a path to that file name. This will only work if you have clean URLs enabled.'),
    '#default_value' => variable_get('xmlsitemap_engines_live_verify', 'LiveSearchSiteAuth.xml'),
  );
  $form['live']['xmlsitemap_engines_live_verify_string'] = array(
    '#type' => 'textfield',
    '#title' => t('Authentication tag'),
    '#description' => t('Windows Live will give you an authentication tag.'),
    '#default_value' => variable_get('xmlsitemap_engines_live_verify_string', ''),
  );
  // Yahoo! fields.
  $form['yahoo'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yahoo!'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _xmlsitemap_engines_add_submission_url_fields($form, 'yahoo', 'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=[sitemap]');
  $form['yahoo']['xmlsitemap_engines_yahoo_verify'] = array(
    '#type' => 'textfield',
    '#title' => t('Verification link'),
    '#description' => t('Before allowing you to view additional information, Yahoo! will ask you to verify that you control this site by creating a file with a certain name. Enter that name here, and XML sitemap will create a path to that file name. This will only work if you have clean URLs enabled.'),
    '#default_value' => variable_get('xmlsitemap_engines_yahoo_verify', ''),
  );
  $form['yahoo']['xmlsitemap_engines_yahoo_verify_string'] = array(
    '#type' => 'textfield',
    '#title' => t('Authentication key'),
    '#description' => t('Yahoo! will ask you to put an authentication key in the verification file.'),
    '#default_value' => variable_get('xmlsitemap_engines_yahoo_verify_string', ''),
  );
  $form = system_settings_form($form);
  $form['#submit'][] = 'xmlsitemap_engines_settings_submit';
  return $form;
}

/**
 * Submit the settings form.
 */
function xmlsitemap_engines_settings_submit($form, &$form_state) {
  variable_set('menu_rebuild_needed', TRUE);
}

/*****************************************************************************
 * Private functions.
 ****************************************************************************/


function _xmlsitemap_engines_add_submission_url_fields(&$form, $id, $default_url) {
  $form[$id]['submission_url'] = array(
    '#type' => 'fieldset',
    '#title' => t('Submission URL'),
  );
  $form[$id]['submission_url']['inline'] = array(
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>'
  );
  $form[$id]['submission_url']['inline']["xmlsitemap_engines_{$id}_submit"] = array(
    '#type' => 'checkbox',
    '#title' => '',
    '#default_value' => variable_get("xmlsitemap_engines_{$id}_submit", FALSE),
  );
  $form[$id]['submission_url']['inline']["xmlsitemap_engines_{$id}_url"] = array(
    '#type' => 'textfield',
    '#title' => '',
    '#default_value' => variable_get("xmlsitemap_engines_{$id}_url", $default_url),
  );
  $form[$id]['submission_url']['description'] = array(
    '#value' => t('The URL to submit the sitemap to; use %placeholder instead of the sitemap URL. Select the checkbox to enable the sitemap submission to this URL.', array('%placeholder' => '[sitemap]')),
    '#prefix' => '<div class="description">',
    '#suffix' => '</div>',
  );
  
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
