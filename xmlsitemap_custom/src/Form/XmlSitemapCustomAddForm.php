<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Form\XmlSitemapCustomAddForm.
 */

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Language\LanguageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;

/**
 * Provides a form for adding a custom link.
 */
class XmlSitemapCustomAddForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, XmlSitemapLinkStorageInterface $link_storage) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
    $this->formBuilder = $form_builder;
    $this->aliasManager = $alias_manager;
    $this->linkStorage = $link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('form_builder'), $container->get('language_manager'), $container->get('path.alias_manager'), $container->get('xmlsitemap.link_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $query = db_select('xmlsitemap', 'x');
    $query->addExpression('MAX(id)');
    $id = $query->execute()->fetchField();
    $link = array();
    $link += array(
      'id' => $id + 1,
      'loc' => '',
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'lastmod' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );

    $form['type'] = array(
      '#type' => 'value',
      '#value' => 'custom',
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $link['id'],
    );
    $form['loc'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to link'),
      '#field_prefix' => url('', array('absolute' => TRUE)),
      '#default_value' => $link['loc'] ? drupal_get_path_alias($link['loc'], $link['language']) : '',
      '#required' => TRUE,
      '#size' => 30,
    );
    $form['priority'] = array(
      '#type' => 'select',
      '#title' => t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($link['priority'], 1),
      '#description' => t('The priority of this URL relative to other URLs on your site.'),
    );
    $form['changefreq'] = array(
      '#type' => 'select',
      '#title' => t('Change frequency'),
      '#options' => array(0 => t('None')) + xmlsitemap_get_changefreq_options(),
      '#default_value' => $link['changefreq'],
      '#description' => t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    );
    $languages = $this->languageManager->getLanguages();
    $languages_list = array();
    foreach ($languages as $key => $value) {
      $languages_list[$key] = $value->getName();
    }
    $form['language'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#default_value' => $link['language'],
      '#options' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => t('Language neutral')) + $languages_list,
      '#access' => $languages_list,
    );

    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 5,
    );
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/config/search/xmlsitemap/custom'),
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $link = &$form_state['values'];

    // Make sure we trim and normalize the path first.
    $link['loc'] = trim($link['loc']);
    $link['loc'] = $this->aliasManager->getPathByAlias($link['loc'], $link['language']);
    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->condition('loc', $link['loc']);
    $query->condition('status', 1);
    $query->condition('access', 1);
    $query->condition('language', $link['language']);
    $result = $query->execute()->fetchAssoc();
    if ($result != FALSE) {
      $this->formBuilder->setErrorByName('loc', $form_state, t('There is already an existing link in the sitemap with the path %link.', array('%link' => $link['loc'])));
    }
    try {
      $client = new Client();
      $res = $client->get(url(NULL, array('absolute' => TRUE)) . $link['loc']);
    }
    catch (ClientException $e) {
      $this->formBuilder->setErrorByName('loc', $form_state, t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $link['loc'])));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $link = $form_state['values'];
    $this->linkStorage->save($link);
    drupal_set_message(t('The custom link for %loc was saved.', array('%loc' => $link['loc'])));

    $form_state['redirect_route']['route_name'] = 'xmlsitemap_custom.list';
  }

}
