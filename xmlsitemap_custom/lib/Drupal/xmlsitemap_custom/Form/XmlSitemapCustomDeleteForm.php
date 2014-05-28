<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Form\XmlSitemapCustomAddForm.
 */

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;

class XmlSitemapCustomDeleteForm extends ConfirmFormBase {

  protected $link;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_delete';
  }

  public function buildForm(array $form, array &$form_state, $link = '') {
    $this->link = $link;
    parent::buildForm($form, $form_state);
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
  public function getCancelRoute() {
    return new Url('xmlsitemap_custom.list');
  }

  /**
   * {@inheritdoc}
   */
  function getQuestion() {
    return t('Are you sure you want to delete %link?', array('%link' => $this->link));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    xmlsitemap_link_delete('custom', $this->link);
    drupal_set_message(t('The custom link for %loc has been deleted.', array('%loc' => $this->link)));
    watchdog('xmlsitemap', 'The custom link for %loc has been deleted.', array('%loc' => $this->link), WATCHDOG_NOTICE);
    $form_state['redirect']['route'] = 'admin/config/search/xmlsitemap/custom';
  }

}
