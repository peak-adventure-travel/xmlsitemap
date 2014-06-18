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
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AnonymousUserSession;
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
   * Constructs a ContentLanguageSettingsForm object.
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
  public function buildForm(array $form, array &$form_state) {
    $entity_types = $this->entityManager->getDefinitions();
    $labels = array();
    $default = array();
    $anonymous_user = new AnonymousUserSession();
    $bundles = $this->entityManager->getAllBundleInfo();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      $access_controller = $this->entityManager->getAccessController($entity_type->id());
      if (!$access_controller) {
        continue;
      }
      $entities = $this->entityManager->getStorage($entity_type_id)->loadMultiple();
      if (!$entities) {
        continue;
      }
      $entity = reset($entities);
      if (!$access_controller->access($entity, 'view', LanguageInterface::LANGCODE_DEFAULT, $anonymous_user)) {
        continue;
      }

      $labels[$entity_type_id] = $entity_type->getLabel() ? : $entity_type_id;
      $default[$entity_type_id] = FALSE;
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
        '#title' => $label,
        '#type' => 'container',
        '#entity_type' => $entity_type_id,
        '#theme' => 'language_content_settings_table',
        '#bundle_label' => $entity_type->getBundleLabel() ? : $label,
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
          'language' => array(
            '#type' => 'language_configuration',
            '#entity_information' => array(
              'entity_type' => $entity_type_id,
              'bundle' => $bundle,
            ),
            '#default_value' => $language_configuration[$entity_type_id][$bundle],
          ),
        );
      }
    }

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Save');

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
