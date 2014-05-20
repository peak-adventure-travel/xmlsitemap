<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapDeleteForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a Example.
 */
class XmlSitemapDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'xmlsitemap.admin_search_list',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Category %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect'] = 'admin/config/search/xmlsitemap';
  }

}
