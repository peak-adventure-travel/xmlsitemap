<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapEntitiesSettingsForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure what entities will be included in sitemap
 */
class XmlSitemapEntitiesSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_config_entities_settings_form';
  }

  /**
   * Constructs a XmlSitemapEntitiesSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xmlsitemap.settings');
    $entity_types = $this->entityManager->getDefinitions();
    $labels = array();
    $default = array();
    $anonymous_user = new AnonymousUserSession();
    $bundles = $this->entityManager->getAllBundleInfo();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }

      $labels[$entity_type_id] = $entity_type->getLabel() ? : $entity_type_id;
    }

    asort($labels);

    $form = array(
      '#labels' => $labels,
    );

    $form['entity_types'] = array(
      '#title' => $this->t('Custom sitemap entities settings'),
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $default,
    );


    $form['settings'] = array('#tree' => TRUE);

    foreach ($labels as $entity_type_id => $label) {
      $entity_type = $entity_types[$entity_type_id];

      $form['settings'][$entity_type_id] = array(
        '#type' => 'container',
        '#entity_type' => $entity_type_id,
        '#theme' => 'xmlsitemap_content_settings_table',
        '#bundle_label' => $entity_type->getBundleLabel() ? $entity_type->getBundleLabel() : $label,
        '#title' => $entity_type->getBundleLabel() ? $entity_type->getBundleLabel() : $label,
        '#states' => array(
          'visible' => array(
            ':input[name="entity_types[' . $entity_type_id . ']"]' => array('checked' => TRUE),
          ),
        ),
      );
      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $form['settings'][$entity_type_id][$bundle]['settings'] = array(
          '#type' => 'item',
          '#label' => $bundle_info['label'],
          '#settings_link' => l('Configure', 'admin/config/search/xmlsitemap/settings/' . $entity_type_id . '/' . $bundle, array('query' => drupal_get_destination())),
          'bundle' => array(
            '#type' => 'checkbox',
            '#default_value' => xmlsitemap_link_bundle_check_enabled($entity_type_id, $bundle)
          ),
        );
        if (xmlsitemap_link_bundle_check_enabled($entity_type_id, $bundle)) {
          $default[$entity_type_id] = $entity_type_id;
        }
      }
    }
    $form['entity_types']['#default_value'] = $default;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = $this->entityManager->getAllBundleInfo();
    $entity_values = $form_state['values']['entity_types'];
    $config = $this->config('xmlsitemap.settings');
    foreach ($entity_values as $key => $value) {
      if ($value) {
        foreach ($bundles[$key] as $bundle_key => $bundle_value) {
          if (!$form_state['values']['settings'][$key][$bundle_key]['settings']['bundle']) {
            xmlsitemap_link_bundle_delete($key, $bundle_key, TRUE);
          }
          else {
            if (!xmlsitemap_link_bundle_check_enabled($key, $bundle_key)) {
              xmlsitemap_link_bundle_enable($key, $bundle_key);
            }
          }
        }
      }
      else {
        foreach ($bundles[$key] as $bundle_key => $bundle_value) {
          xmlsitemap_link_bundle_delete($key, $bundle_key, TRUE);
        }
      }
    }
    parent::submitForm($form, $form_state);
  }

}
