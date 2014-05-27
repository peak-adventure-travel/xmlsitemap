<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Form\XmlSitemapCustomAddForm.
 */

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AnonymousUserSession;

class XmlSitemapCustomAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //module_load_include('inc', 'xmlsitemap', 'xmlsitemap.admin');
    //_xmlsitemap_set_breadcrumb('admin/config/search/xmlsitemap/custom');
    \Drupal::moduleHandler()->loadInclude('xmlsitemap', 'inc', 'xmlsitemap.admin');
    $link = array();
    $link += array(
      'id' => db_query("SELECT MAX(id) FROM {xmlsitemap} WHERE type = 'custom'")->fetchField() + 1,
      'loc' => '',
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'lastmod' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );

    $form['type'] = array(
      '#type' => 'value',
      '#value' => 'custom',
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $link['id'],
    );
    $form['loc'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to link'),
      '#field_prefix' => url('', array('absolute' => TRUE)),
      '#default_value' => $link['loc'] ? drupal_get_path_alias($link['loc'], $link['language']) : '',
      '#required' => TRUE,
      '#size' => 30,
    );
    $form['priority'] = array(
      '#type' => 'select',
      '#title' => t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($link['priority'], 1),
      '#description' => t('The priority of this URL relative to other URLs on your site.'),
    );
    $form['changefreq'] = array(
      '#type' => 'select',
      '#title' => t('Change frequency'),
      '#options' => array(0 => t('None')) + xmlsitemap_get_changefreq_options(),
      '#default_value' => $link['changefreq'],
      '#description' => t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    );
    $languages = \Drupal::languageManager()->getLanguages();
    $languages_list = array();
    foreach ($languages as $key => $value) {
      $languages_list[$key] = $value->getName();
    }
    $form['language'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#default_value' => $link['language'],
      '#options' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => t('Language neutral')) + $languages_list,
      '#access' => $languages_list,
    );

    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 5,
    );
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/config/search/xmlsitemap/custom'),
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    /* $link = &$form_state['values'];

      // Make sure we trim and normalize the path first.
      $link['loc'] = trim($link['loc']);
      $link['loc'] = \Drupal::service('path.alias_manager')->getPathByAlias($link['loc'], $link['language']);
      // Test anonymous user access to the custom link paths.
      xmlsitemap_switch_user(0);
      $menu_item = menu_get_item($link['loc']);
      xmlsitemap_restore_user();

      // Since the menu item access results are cached, manually check the current path.
      $anonymous_user = new AnonymousUserSession();
      if ($menu_item && strpos($link['loc'], 'admin/config/search/xmlsitemap/custom') === 0 && !$anonymous_user->hasPermission('administer xmlsitemap')) {
      $menu_item['access'] = FALSE;
      }

      if (db_query_range("SELECT 1 FROM {xmlsitemap} WHERE type <> 'custom' AND loc = :loc AND status = 1 AND access = 1 AND language IN (:languages)", 0, 1, array(':loc' => $link['loc'], ':languages' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED, $link['language'])))->fetchField()) {
      form_set_error('loc', t('There is already an existing link in the sitemap with the path %link.', array('%link' => $link['loc'])));
      }
      elseif (empty($menu_item['access']) && !is_readable('./' . $link['loc'])) {
      // @todo Harden this file exists check to make sure we can't link to files
      // like .htaccess.
      form_set_error('loc', t('The custom link %link is either invalid or it cannot be accessed by anonymous users.', array('%link' => $link['loc'])));
      } */
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $link = $form_state['values'];
    xmlsitemap_link_save($link);
    drupal_set_message(t('The custom link for %loc was saved.', array('%loc' => $link['loc'])));
    $form_state['redirect'] = 'admin/config/search/xmlsitemap/custom';
  }

}
