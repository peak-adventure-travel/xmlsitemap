<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapInterface.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a XmlSitemap entity.
 */
interface XmlSitemapInterface extends EntityInterface {

  /**
   * Returns the identifier.
   *
   * @return int
   *   The entity identifier.
   */
  public function id();

  /**
   * Set the xmlsitemap id.
   *
   * @param string $smid
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The called xmlsitemap entity.
   */
  public function setId($smid);

  /**
   * Returns the entity UUID (Universally Unique Identifier).
   *
   * The UUID is guaranteed to be unique and can be used to identify an entity
   * across multiple systems.
   *
   * @return string
   *   The UUID of the entity.
   */
  public function uuid();

  /**
   * Returns the context.
   *
   * @return array
   *   The array with sitemap context.
   */
  public function xmlSitemapContext();

  /**
   * Set the xmlsitemap context.
   *
   * @param array $context
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The called xmlsitemap entity.
   */
  public function setXmlSitemapContext($context);

  /**
   * Returns the number of chunks in sitemap.
   *
   * @return int
   *   The number of chunks in sitemap.
   */
  public function chunks();

  /**
   * Set the xmlsitemap chunks number.
   *
   * @param int $chunks
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The called xmlsitemap entity.
   */
  public function setChunks($chunks);

  /**
   * Returns the number of links in sitemap.
   *
   * @return int
   *   The number of links in sitemap.
   */
  public function links();

  /**
   * Set the xmlsitemap links number.
   *
   * @param int $links
   *    
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The called xmlsitemap entity.
   */
  public function setLinks($links);

  /**
   * Returns maximum size of sitemap.
   *
   * @return int
   *   Maximum size of sitemap.
   */
  public function maxFileSize();

  /**
   * Set maximum size of sitemap.
   *
   * @param int $max_file_size
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The called xmlsitemap entity.
   */
  public function setMaxFileSize($max_file_size);

  /**
   * Defines the base fields of the entity type.
   *
   * @param string $entity_type
   *   Name of the entity type
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of entity field definitions, keyed by field name.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);
}
