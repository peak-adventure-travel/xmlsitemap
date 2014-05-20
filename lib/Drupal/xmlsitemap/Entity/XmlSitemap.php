<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Entity\XmlSitemap.
 */

namespace Drupal\xmlsitemap\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\xmlsitemap\XmlSitemapInterface;

/**
 * Defines the XmlSitemap entity.
 *
 * @ConfigEntityType(
 *   id = "xmlsitemap",
 *   label = @Translation("XmlSitemap"),
 *   controllers = {
 *     "list_builder" = "Drupal\xmlsitemap\XmlSitemapListBuilder",
 *     "form" = {
 *       "add" = "Drupal\xmlsitemap\Form\XmlSitemapForm",
 *       "edit" = "Drupal\xmlsitemap\Form\XmlSitemapForm",
 *       "delete" = "Drupal\xmlsitemap\Form\XmlSitemapDeleteForm"
 *     }
 *   },
 *   config_prefix = "xmlsitemap",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "xmlsitemap.admin_edit",
 *     "delete-form" = "xmlsitemap.admin_delete"
 *   }
 * )
 */
class XmlSitemap extends ConfigEntityBase implements XmlSitemapInterface {

  /**
   * The XmlSitemap ID.
   *
   * @var string
   */
  public $id;

  /**
   * The XmlSitemap UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The XmlSitemap label.
   *
   * @var string
   */
  public $label;

  public static function baseFieldDefinitions(\EntityTypeInterface $entity_type) {

  }

// implementing the interface.
}
