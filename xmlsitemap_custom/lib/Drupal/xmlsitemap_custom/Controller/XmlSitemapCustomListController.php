<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Controller\XmlSitemapCustomListController.
 */

namespace Drupal\xmlsitemap_custom\Controller;

/**
 * Builds the list table for all custom links.
 */
class XmlSitemapCustomListController {

  public function render() {
    $build['xmlsitemap_add_custom'] = array(
      '#type' => 'link',
      '#title' => 'Add custom link',
      '#href' => 'admin/config/search/xmlsitemap/custom/add'
    );
    $header = array(
      'loc' => array('data' => t('Location'), 'field' => 'loc', 'sort' => 'asc'),
      'priority' => array('data' => t('Priority'), 'field' => 'priority'),
      'changefreq' => array('data' => t('Change frequency'), 'field' => 'changefreq'),
      'language' => array('data' => t('Language'), 'field' => 'language'),
      'operations' => array('data' => t('Operations')),
    );

    $rows = array();
    $destination = drupal_get_destination();

    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->execute();

    foreach ($result as $link) {
      $language = \Drupal::languageManager()->getLanguage($link->language);
      $row = array();
      $row['loc'] = l($link->loc, $link->loc);
      $row['priority'] = number_format($link->priority, 1);
      $row['changefreq'] = $link->changefreq ? drupal_ucfirst(xmlsitemap_get_changefreq($link->changefreq)) : t('None');
      if (isset($header['language'])) {
        $row['language'] = t($language->name);
      }
      $operations = array();
      $operations['edit'] = xmlsitemap_get_operation_link('admin/config/search/xmlsitemap/custom/edit/' . $link->id, array('title' => t('Edit'), 'modal' => TRUE));
      $operations['delete'] = xmlsitemap_get_operation_link('admin/config/search/xmlsitemap/custom/delete/' . $link->id, array('title' => t('Delete'), 'modal' => TRUE));
      $row['operations'] = array(
        'data' => array(
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => array('class' => array('links', 'inline')),
        ),
      );
      $rows[] = $row;
    }

    // @todo Convert to tableselect
    $build['xmlsitemap_custom_table'] = array(
      '#type' => 'tableselect',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No custom links available.') . ' ' . l(t('Add custom link'), 'admin/config/search/xmlsitemap/custom/add', array('query' => $destination)),
    );
    $build['xmlsitemap_custom_pager'] = array('#theme' => 'pager');

    return $build;
  }

}
