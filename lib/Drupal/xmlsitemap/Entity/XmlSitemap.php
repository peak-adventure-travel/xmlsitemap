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
 *     "id" = "smid",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "chunks" = "chunks",
 *     "links" = "links",
 *     "max_filesize" = "max_filesize",
 *     "context" = "context",
 *     "updated" = "updated"
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

  /**
   * The XmlSitemap chunks number.
   *
   * @var int
   */
  protected $chunks;

  /**
   * The XmlSitemap links number.
   *
   * @var int
   */
  protected $links;

  /**
   * Maximum size for a sitemap.
   *
   * @var int
   */
  protected $max_filesize;

  /**
   * The XmlSitemap context.
   *
   * @var array
   */
  protected $context;

  /**
   * Last time when sitemap was updated.
   *
   * @var int
   */
  protected $updated;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getChunks() {
    return $this->chunks;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxFileSize() {
    return $this->max_filesize;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdated() {
    return $this->updated;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setChunks($chunks) {
    $this->chunks = $chunks;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinks($links) {
    $this->links = $links;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMaxFileSize($max_filesize) {
    $this->max_filesize = $max_filesize;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($context) {
    $this->context = $context;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdated($updated) {
    $this->updated = $updated;
    return $this;
  }

}
