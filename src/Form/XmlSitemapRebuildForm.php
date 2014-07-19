<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapRebuildForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\MapArray;
use Drupal\Component\Utility\UrlHelper;

/**
 * Configure xmlsitemap settings for this site.
 */
class XmlSitemapRebuildForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_admin_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $request = $this->getRequest();
    if (!$request->request && !\Drupal::state()->get('rebuild_needed')) {
      if (!\Drupal::state()->get('regenerate_needed')) {
        drupal_set_message(t('Your sitemap is up to date and does not need to be rebuilt.'), 'error');
      }
      else {
        $request->query->set('destination','admin/config/search/xmlsitemap');
        drupal_set_message(t('A rebuild is not necessary. If you are just wanting to regenerate the XML sitemap files, you can <a href="@link-cron">run cron manually</a>.', array('@link-cron' => url('admin/reports/status/run-cron', array('query' => drupal_get_destination())))), 'warning');
        $this->setRequest($request);
      }
    }

    // Build a list of rebuildable link types.
    module_load_include('generate.inc', 'xmlsitemap');
    $rebuild_types = xmlsitemap_get_rebuildable_link_types();
    $rebuild_types = array_combine($rebuild_types, $rebuild_types);
    $form['entities'] = array(
      '#type' => 'select',
      '#title' => t("Select which link types you would like to rebuild"),
      '#description' => t('If no link types are selected, the sitemap files will just be regenerated.'),
      '#multiple' => TRUE,
      '#options' => $rebuild_types,
      '#default_value' => \Drupal::state()->get('rebuild_needed') || !\Drupal::state()->get('developer_mode') ? $rebuild_types : array(),
      '#access' => \Drupal::state()->get('developer_mode'),
    );
    $form['save_custom'] = array(
      '#type' => 'checkbox',
      '#title' => t('Save and restore any custom inclusion and priority links.'),
      '#default_value' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Save any changes to the frontpage link.
    module_load_include('generate.inc', 'xmlsitemap');
    $batch = xmlsitemap_rebuild_batch($form_state['values']['entities'], $form_state['values']['save_custom']);
    batch_set($batch);
    $form_state['redirect'] = 'admin/config/search/xmlsitemap';

    parent::submitForm($form, $form_state);
  }

}
