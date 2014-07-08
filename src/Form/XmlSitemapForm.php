<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\LanguageInterface;

class XmlSitemapForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_sitemap_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    if ($this->entity->getContext() == NULL) {
      $this->entity->context = array();
      $this->entity->setOriginalId(NULL);
    }
    $xmlsitemap = $this->entity;
    $form['#entity'] = $xmlsitemap;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $xmlsitemap->label(),
      '#description' => $this->t("Label for the Example."),
      '#required' => TRUE,
    );
    $form['context'] = array(
      '#tree' => TRUE,
    );
    $visible_children = element_get_visible_children($form['context']);
    if (empty($visible_children)) {
      $form['context']['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('There are currently no XML sitemap contexts available.') . '</p>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    if (!isset($form_state['values']['context'])) {
      $form_state['values']['context'] = xmlsitemap_get_current_context();
    }
    if (isset($form_state['values']['context']['language']) && $form_state['values']['context']['language'] == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      unset($form_state['values']['context']['language']);
    }
    $this->entity->context = $form_state['values']['context'];
    $context = $form_state['values']['context'];
    $this->entity->label = $form_state['values']['label'];
    $this->entity->id = xmlsitemap_sitemap_get_context_hash($context);

    try {
      $status = $this->entity->save();
      if ($status == SAVED_NEW) {
        drupal_set_message($this->t('Saved the %label sitemap.', array(
              '%label' => $this->entity->label(),
        )));
      }
      else if ($status == SAVED_UPDATED) {
        drupal_set_message($this->t('Updated the %label sitemap.', array(
              '%label' => $this->entity->label(),
        )));
      }
    }
    catch (EntityStorageException $ex) {
      drupal_set_message($this->t('There is another sitemap saved with the same context.'), 'error');
    }

    $form_state['redirect'] = 'admin/config/search/xmlsitemap';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $form_state['redirect'] = array('admin/config/search/xmlsitemap/' . $this->entity->id() . '/delete', array('query' => $destination));
  }

}
