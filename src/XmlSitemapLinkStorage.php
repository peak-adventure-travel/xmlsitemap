<?php

/**
 * @file
 * Definition of Drupal\xmlsitemap\XmlSitemapLinkStorage.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a class to save/update/delete/load xmlsitemap links.
 */
class XmlSitemapLinkStorage {

  /**
   * Saves or updates a sitemap link.
   *
   * @param $link
   *   An array with a sitemap link.
   */
  public static function linkSave(array $link) {
    $link += array(
      'access' => 1,
      'status' => 1,
      'status_override' => 0,
      'lastmod' => 0,
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'priority_override' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );

    // Allow other modules to alter the link before saving.
    \Drupal::moduleHandler()->alter('xmlsitemap_link', $link);

    // Temporary validation checks.
    // @todo Remove in final?
    if ($link['priority'] < 0 || $link['priority'] > 1) {
      trigger_error(t('Invalid sitemap link priority %priority.<br />@link', array('%priority' => $link['priority'], '@link' => var_export($link, TRUE))), E_USER_ERROR);
    }
    if ($link['changecount'] < 0) {
      trigger_error(t('Negative changecount value. Please report this to <a href="@516928">@516928</a>.<br />@link', array('@516928' => 'http://drupal.org/node/516928', '@link' => var_export($link, TRUE))), E_USER_ERROR);
      $link['changecount'] = 0;
    }

    $existing = db_query_range("SELECT loc, access, status, lastmod, priority, changefreq, changecount, language FROM {xmlsitemap} WHERE type = :type AND id = :id", 0, 1, array(':type' => $link['type'], ':id' => $link['id']))->fetchAssoc();

    // Check if this is a changed link and set the regenerate flag if necessary.
    if (!\Drupal::state()->get('xmlsitemap_regenerate_needed')) {
      self::checkChangedLink($link, $existing, TRUE);
    }

    // Save the link and allow other modules to respond to the link being saved.
    if ($existing) {
      drupal_write_record('xmlsitemap', $link, array('type', 'id'));
      \Drupal::moduleHandler()->invokeAll('xmlsitemap_link_update', array($link));
    }
    else {
      drupal_write_record('xmlsitemap', $link);
      \Drupal::moduleHandler()->invokeAll('xmlsitemap_link_insert', array($link));
    }

    return $link;
  }

  /**
   * Check if there is sitemap link is changed from the existing data.
   *
   * @param $link
   *   An array of the sitemap link.
   * @param $original_link
   *   An optional array of the existing data. This should only contain the
   *   fields necessary for comparison. If not provided the existing data will be
   *   loaded from the database.
   * @param $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   * @return
   *   TRUE if the link is changed, or FALSE otherwise.
   */
  public static function checkChangedLink(array $link, $original_link = NULL, $flag = FALSE) {
    $changed = FALSE;

    if ($original_link === NULL) {
      // Load only the fields necessary for data to be changed in the sitemap.
      $original_link = db_query_range("SELECT loc, access, status, lastmod, priority, changefreq, changecount, language FROM {xmlsitemap} WHERE type = :type AND id = :id", 0, 1, array(':type' => $link['type'], ':id' => $link['id']))->fetchAssoc();
    }

    if (!$original_link) {
      if ($link['access'] && $link['status']) {
        // Adding a new visible link.
        $changed = TRUE;
      }
    }
    else {
      if (!($original_link['access'] && $original_link['status']) && $link['access'] && $link['status']) {
        // Changing a non-visible link to a visible link.
        $changed = TRUE;
      }
      elseif ($original_link['access'] && $original_link['status'] && array_diff_assoc($original_link, $link)) {
        // Changing a visible link
        $changed = TRUE;
      }
    }

    if ($changed && $flag) {
      \Drupal::state()->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * Check if there is a visible sitemap link given a certain set of conditions.
   *
   * @param $conditions
   *   An array of values to match keyed by field.
   * @param $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   * @return
   *   TRUE if there is a visible link, or FALSE otherwise.
   */
  public static function checkChangedLinks(array $conditions = array(), array $updates = array(), $flag = FALSE) {
    // If we are changing status or access, check for negative current values.
    $conditions['status'] = (!empty($updates['status']) && empty($conditions['status'])) ? 0 : 1;
    $conditions['access'] = (!empty($updates['access']) && empty($conditions['access'])) ? 0 : 1;

    $query = db_select('xmlsitemap');
    $query->addExpression('1');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $query->range(0, 1);
    $changed = $query->execute()->fetchField();

    if ($changed && $flag) {
      \Drupal::state()->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * Delete a specific sitemap link from the database.
   *
   * If a visible sitemap link was deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param $entity_type
   *   A string with the entity type.
   * @param $entity_id
   *   An integer with the entity ID.
   * @return
   *   The number of links that were deleted.
   */
  public static function linkDelete($entity_type, $entity_id) {
    $conditions = array('type' => $entity_type, 'id' => $entity_id);
    return self::linkDeleteMultiple($conditions);
  }

  /**
   * Delete multiple sitemap links from the database.
   *
   * If visible sitemap links were deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   * @return
   *   The number of links that were deleted.
   */
  public static function linkDeleteMultiple(array $conditions) {
    // Because this function is called from sub-module uninstall hooks, we have
    // to manually check if the table exists since it could have been removed
    // in xmlsitemap_uninstall().
    // @todo Remove this check when http://drupal.org/node/151452 is fixed.
    if (!db_table_exists('xmlsitemap')) {
      return FALSE;
    }

    if (!\Drupal::state()->get('xmlsitemap_regenerate_needed')) {
      self::checkChangedLinks($conditions, array(), TRUE);
    }

    // @todo Add a hook_xmlsitemap_link_delete() hook invoked here.

    $query = db_delete('xmlsitemap');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    return $query->execute();
  }

  /**
   * Perform a mass update of sitemap data.
   *
   * If visible links are updated, this will automatically set the regenerate
   * needed flag to TRUE.
   *
   * @param $updates
   *   An array of values to update fields to, keyed by field name.
   * @param $conditions
   *   An array of values to match keyed by field.
   * @return
   *   The number of links that were updated.
   */
  public static function linkUpdateMultiple($updates = array(), $conditions = array(), $check_flag = TRUE) {
    // If we are going to modify a visible sitemap link, we will need to set
    // the regenerate needed flag.
    if ($check_flag && !\Drupal::state()->get('xmlsitemap_regenerate_needed')) {
      self::checkChangedLinks($conditions, $updates, TRUE);
    }

    // Process updates.
    $query = db_update('xmlsitemap');
    $query->fields($updates);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    return $query->execute();
  }

  /**
   * Load a specific sitemap link from the database.
   *
   * @param $entity_type
   *   A string with the entity type.
   * @param $entity_id
   *   An integer with the entity ID.
   * @return
   *   A sitemap link (array) or FALSE if the conditions were not found.
   */
  public static function linkLoad($entity_type, $entity_id) {
    $link = self::linkLoadMultiple(array('type' => $entity_type, 'id' => $entity_id));
    return $link ? reset($link) : FALSE;
  }

  /**
   * Load sitemap links from the database.
   *
   * @param $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   * @return
   *   An array of sitemap link arrays.
   */
  public static function linkLoadMultiple(array $conditions = array()) {
    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');

    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    $links = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $links;
  }

}
