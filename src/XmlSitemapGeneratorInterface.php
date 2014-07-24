<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapGeneratorInterface.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface defining a XmlSitemapGenerator service.
 */
interface XmlSitemapGeneratorInterface {

  /**
   * Given an internal Drupal path, return the alias for the path.
   *
   * This is similar to drupal_get_path_alias(), but designed to fetch all alises
   * at once so that only one database query is executed instead of several or
   * possibly thousands during sitemap generation.
   *
   * @param string $path
   *   An internal Drupal path.
   * @param Drupal\Core\Language\LanguageInterface $language
   *   A language code to use when looking up the paths.
   */
  public function getPathAlias($path, $language);

  /**
   * Perform operations before rebuilding the sitemap.
   */
  public function regenerateBefore();

  /**
   * Get how much memory was used
   *
   * @param bool $start
   *
   */
  public function getMemoryUsage($start = FALSE);

  /**
   * Calculate the optimal PHP memory limit for sitemap generation.
   *
   * This function just makes a guess. It does not take into account
   * the currently loaded modules.
   */
  public function getOptimalMemoryLimit();

  /**
   * Calculate the optimal memory level for sitemap generation.
   *
   * @param $new_limit
   *   An optional PHP memory limit in bytes. If not provided, the value of
   *   getOptimalMemoryLimit() will be used.
   */
  public function setMemoryLimit($new_limit = NULL);

  /**
   * Generate one page (chunk) of the sitemap.
   *
   * @param $sitemap
   *   An unserialized data array for an XML sitemap.
   * @param $page
   *   An integer of the specific page of the sitemap to generate.
   */
  public function generatePage(XmlSitemapInterface $sitemap, $page);

  /**
   * Generate one chunk of the sitemap.
   *
   * @param $sitemap
   *   An unserialized data array for an XML sitemap.
   * @param XmlSitemapWriter $writer
   *   XML writer object
   * @param $page
   *   An integer of the specific page of the sitemap to generate.
   */
  public function generateChunk(XmlSitemapInterface $sitemap, XmlSitemapWriter $writer, $chunk);

  /**
   * Generate the index sitemap.
   *
   * @param $sitemap
   *   An unserialized data array for an XML sitemap.
   */
  public function generateIndex(XmlSitemapInterface $sitemap);

  /**
   * Batch information callback for regenerating the sitemap files.
   *
   * @param $smids
   *   An optional array of XML sitemap IDs. If not provided, it will load all
   *   existing XML sitemaps.
   */
  public function regenerateBatch(array $smids = array());

  /**
   * Batch callback; generate all pages of a sitemap.
   */
  public function regenerateBatchGenerate($smid, array &$context);

  /**
   * Batch callback; generate the index page of a sitemap.
   */
  public function regenerateBatchGenerateIndex($smid, array &$context);

  /**
   * Batch callback; sitemap regeneration finished.
   */
  public function regenerateBatchFinished($success, $results, $operations, $elapsed);

  /**
   * Batch information callback for rebuilding the sitemap data.
   */
  public function rebuildBatch(array $entities, $save_custom = FALSE);

  /**
   * Batch callback; clear sitemap links for entites.
   */
  public function rebuildBatchClear(array $entities, $save_custom, &$context);

  /**
   * Batch callback; fetch and add the sitemap links for a specific entity.
   */
  public function rebuildBatchFetch($entity, &$context);

  /**
   * Batch callback; sitemap rebuild finished.
   */
  public function rebuildBatchFinished($success, $results, $operations, $elapsed);

  /**
   * Set variables during the batch process
   */
  public function batchVariableSet(array $variables);
}
