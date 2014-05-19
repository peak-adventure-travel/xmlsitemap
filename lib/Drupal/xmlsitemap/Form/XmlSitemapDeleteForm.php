<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapDeleteForm
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a form for deleting an xmlsitemap entity.
 */
class XmlSitemapDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'xmlsitemap.admin_search',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('content', '@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->id()));
    $form_state['redirect_route']['route_name'] = 'xmlsitemap.admin_search';
  }

}
