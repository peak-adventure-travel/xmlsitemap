<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_engines\Form\XmlSitemapEnginesSettingsForm.
 */

namespace Drupal\xmlsitemap_engines\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\UrlHelper;

/**
 * Configure xmlsitemap engines settings for this site.
 */
class XmlSitemapEnginesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_engines_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Build the list of support engines for the checkboxes options.
    $engines = xmlsitemap_engines_get_engine_info();
    $engine_options = array();
    foreach ($engines as $engine => $engine_info) {
      $engine_options[$engine] = $engine_info['name'];
    }
    asort($engine_options);

    $form['xmlsitemap_engines_engines'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Submit the sitemap to the following engines'),
      '#default_value' => \Drupal::state()->get('xmlsitemap_engines_engines'),
      '#options' => $engine_options,
    );
    $lifetimes = array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 604800 * 2, 604800 * 4);
    $form['xmlsitemap_engines_minimum_lifetime'] = array(
      '#type' => 'select',
      '#title' => t('Do not submit more often than every'),
      '#options' => array_map('format_interval', array_combine($lifetimes, $lifetimes)),
      '#default_value' => \Drupal::state()->get('xmlsitemap_engines_minimum_lifetime'),
    );
    $form['xmlsitemap_engines_submit_updated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only submit if the sitemap has been updated since the last submission.'),
      '#default_value' => \Drupal::state()->get('xmlsitemap_engines_submit_updated'),
    );
    $form['xmlsitemap_engines_custom_urls'] = array(
      '#type' => 'textarea',
      '#title' => t('Custom submission URLs'),
      '#description' => t('Enter one URL per line. The token [sitemap] will be replaced with the URL to your sitemap. For example: %example-before would become %example-after.', array('%example-before' => 'http://example.com/ping?[sitemap]', '%example-after' => xmlsitemap_engines_prepare_url('http://example.com/ping?[sitemap]', url('sitemap.xml', array('absolute' => TRUE))))),
      '#default_value' => \Drupal::state()->get('xmlsitemap_engines_custom_urls'),
      '#rows' => 2,
      '#wysiwyg' => FALSE
    );

    // Ensure the xmlsitemap_engines variable gets filterd to a simple array.
    $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $custom_urls = preg_split('/[\r\n]+/', $form_state['values']['xmlsitemap_engines_custom_urls'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($custom_urls as $custom_url) {
      $url = xmlsitemap_engines_prepare_url($custom_url, '');
      if (!UrlHelper::isValid($url, TRUE)) {
        \Drupal::formBuilder()->setErrorByName($custom_url, $form_state, t('Invalid URL %url.', array('%url' => $custom_url)));
      }
    }
    $form_state['values']['xmlsitemap_engines_custom_urls'] = implode("\n", $custom_urls);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $keys = array(
      'xmlsitemap_engines_engines',
      'xmlsitemap_engines_minimum_lifetime',
      'xmlsitemap_engines_submit_updated',
      'xmlsitemap_engines_custom_urls'
    );
    $values = $form_state['values'];
    foreach ($keys as $key) {
      \Drupal::state()->set($key, $values[$key]);
    }
    parent::submitForm($form, $form_state);
  }

}
