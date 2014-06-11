<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapEntitiesSettingsForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configure what entities will be included in sitemap
 */
class XmlSitemapEntitiesSettingsForm extends ConfigFormBase {

  /**
   * Custom entities that can be included in sitemap.
   * 
   * @var array
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_config_entities_settings_form';
  }

  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->entities = array('menu', 'user', 'taxonomy', 'node');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $default = array();
    foreach ($this->entities as $entity) {
      if (\Drupal::state()->get('xmlsitemap_entity_' . $entity)) {
        $default[$entity] = $entity;
      }
    }
    $form['custom_entity_types'] = array(
      '#title' => $this->t('Select custom entity types that will be introduced in sitemap'),
      '#type' => 'checkboxes',
      '#options' => array_combine($this->entities, array_map('ucwords', $this->entities)),
      '#default_value' => $default
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values']['custom_entity_types'];
    foreach ($this->entities as $entity) {
      \Drupal::state()->set('xmlsitemap_entity_' . $entity, $values[$entity] ? TRUE : FALSE);
    }
    parent::submitForm($form, $form_state);
  }

}
