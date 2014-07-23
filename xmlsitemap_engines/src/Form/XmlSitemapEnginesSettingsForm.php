<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_engines\Form\XmlSitemapEnginesSettingsForm.
 */

namespace Drupal\xmlsitemap_engines\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configure xmlsitemap engines settings for this site.
 */
class XmlSitemapEnginesSettingsForm extends ConfigFormBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $state
   *   The language manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, StateInterface $state) {
    parent::__construct($config_factory);
    $this->formBuilder = $form_builder;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('form_builder'), $container->get('state')
    );
  }

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

    $form['engines'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Submit the sitemap to the following engines'),
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('engines'),
      '#options' => $engine_options,
    );
    $lifetimes = array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 604800 * 2, 604800 * 4);
    $form['minimum_lifetime'] = array(
      '#type' => 'select',
      '#title' => t('Do not submit more often than every'),
      '#options' => array_map('format_interval', array_combine($lifetimes, $lifetimes)),
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('minimum_lifetime'),
    );
    $form['xmlsitemap_engines_submit_updated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only submit if the sitemap has been updated since the last submission.'),
      '#default_value' => $this->state->get('xmlsitemap_engines_submit_updated'),
    );
    $form['custom_urls'] = array(
      '#type' => 'textarea',
      '#title' => t('Custom submission URLs'),
      '#description' => t('Enter one URL per line. The token [sitemap] will be replaced with the URL to your sitemap. For example: %example-before would become %example-after.', array('%example-before' => 'http://example.com/ping?[sitemap]', '%example-after' => xmlsitemap_engines_prepare_url('http://example.com/ping?[sitemap]', url('sitemap.xml', array('absolute' => TRUE))))),
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('custom_urls'),
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
    $custom_urls = preg_split('/[\r\n]+/', $form_state['values']['custom_urls'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($custom_urls as $custom_url) {
      $url = xmlsitemap_engines_prepare_url($custom_url, '');
      if (!UrlHelper::isValid($url, TRUE)) {
        $this->formBuilder->setErrorByName($custom_url, $form_state, t('Invalid URL %url.', array('%url' => $custom_url)));
      }
    }
    $form_state['values']['custom_urls'] = implode("\n", $custom_urls);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $state_variables = xmlsitemap_engines_state_variables();
    $config_variables = xmlsitemap_engines_config_variables();
    $keys = array(
      'engines',
      'minimum_lifetime',
      'xmlsitemap_engines_submit_updated',
      'custom_urls'
    );
    $values = $form_state['values'];
    foreach ($keys as $key) {
      if (isset($state_variables[$key])) {
        $this->state->set($key, $values[$key]);
      }
      else {
        $this->config('xmlsitemap_engines.settings')->set($key, $values[$key]);
      }
    }
    $this->config('xmlsitemap_engines.settings')->save();
    parent::submitForm($form, $form_state);
  }

}
