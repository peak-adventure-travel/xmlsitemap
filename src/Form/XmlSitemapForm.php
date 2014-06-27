<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;

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
      $this->entity->setContext(array());
      $this->entity->setOriginalId(NULL);
    }
    $xmlsitemap = $this->entity;
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
    $this->entity->context = $form_state['values']['context'];
    $context = $form_state['values']['context'];
    $this->entity->label = $form_state['values']['label'];
    $this->entity->id = xmlsitemap_sitemap_get_context_hash($context);
    if (xmlsitemap_sitemap_load_by_context($form_state['values']['context']) != NULL) {
      drupal_set_message($this->t('There is another sitemap saved with the same context.'), 'error');
    }
    else {
      $status = $this->entity->save();
      if ($status) {
        drupal_set_message($this->t('Saved the %label sitemap.', array(
              '%label' => $this->entity->label(),
        )));
      }
      else {
        drupal_set_message($this->t('The %label sitemap was not saved.', array(
              '%label' => $this->entity->label(),
        )));
      }
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
