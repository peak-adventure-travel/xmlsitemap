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
    $xmlsitemap = $this->entity;
    if ($xmlsitemap->isNew()) {
      $context = $xmlsitemap->getContext();
      $xmlsitemap->setId(xmlsitemap_sitemap_get_context_hash($context));
      $xmlsitemap->setContext($context);
    }
    $status = $xmlsitemap->save();
    if ($status) {
      drupal_set_message($this->t('Saved the %label sitemap.', array(
            '%label' => $xmlsitemap->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label sitemap was not saved.', array(
            '%label' => $xmlsitemap->label(),
      )));
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
