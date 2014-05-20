<?php

/**
 * @file
 * Definition of Drupal\xmlsitemap\Form\XmlSitemapForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;

/**
 * Form controller for the xmlsitemap entity edit forms.
 */
class XmlSitemapForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    /* @var $entity \Drupal\xmlsitemap\Entity\XmlSitemap */
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit sitemap</em>');
    }

    /*$entity = $this->entity;
    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => 'UID',
      '#default_value' => $entity->user_id->target_id,
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => -10,
    );
    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->id,
      '#languages' => Language::STATE_ALL,
    );*/
    return parent::form($form,$form_state);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);
    $form_state['redirect_route']['route_name'] = 'xmlsitemap.admin_search_list';
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $entity->save();
  }

}
