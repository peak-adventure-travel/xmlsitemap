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
use Symfony\Component\HttpFoundation\RedirectResponse;

class XmlSitemapCustomDeleteForm extends ConfirmFormBase {

  protected $custom_link;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_delete';
  }

  public function buildForm(array $form, array &$form_state, $link = '') {
    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->condition('id', $link);
    $result = $query->execute();
    $link = $result->fetchAssoc();
    if (!$link) {
      drupal_set_message(t('No valid custom link specified.'),'error');
      return new RedirectResponse('/admin/config/search/xmlsitemap/custom/');
    }
    else {
      $this->custom_link = $link;
    }
    return parent::buildForm($form, $form_state);
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
  public function getQuestion() {
    return t('Are you sure you want to delete %link?', array('%link' => $this->custom_link['loc']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    xmlsitemap_link_delete('custom', $this->custom_link['id']);
    drupal_set_message(t('The custom link for %loc has been deleted.', array('%loc' => $this->link['loc'])));
    watchdog('xmlsitemap', 'The custom link for %loc has been deleted.', array('%loc' => $this->link['loc']), WATCHDOG_NOTICE);
    $form_state['redirect_route']['route_name'] = 'xmlsitemap_custom.list';
  }

}
