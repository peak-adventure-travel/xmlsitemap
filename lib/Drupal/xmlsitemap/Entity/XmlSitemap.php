<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Entity\XmlSitemap.
 */

namespace Drupal\xmlsitemap\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\xmlsitemap\XmlSitemapInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the XmlSitemap entity.
 *
 * @ContentEntityType(
 *   id = "xmlsitemap",
 *   label = @Translation("XmlSitemap entity"),
 *   controllers = {
 *     "list_builder" = "Drupal\xmlsitemap\XmlSitemapListBuilder",
 *     
 *
 *     "form" = {
 *       "add" = "Drupal\xmlsitemap\Form\XmlSitemapForm",
 *       "edit" = "Drupal\xmlsitemap\Form\XmlSitemapForm",
 *       "delete" = "Drupal\xmlsitemap\Form\XmlSitemapDeleteForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "xmlsitemap_sitemap",
 *   admin_permission = "administer xmlsitemap",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "smid",
 *     "uuid" = "uuid",
 *     "chunks" = "chunks",
 *     "links" = "links",
 *     "max_filesize" = "max_filesize",
 *     "context" = "context"
 *   },
 *   links = {
 *     "edit-form" = "xmlsitemap.admin_edit",
 *     "delete-form" = "xmlsitemap.admin_delete"
 *   }
 * )
 */
class XmlSitemap extends ContentEntityBase implements XmlSitemapInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('smid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function chunks() {
    return $this->get('chunks')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function links() {
    return $this->get('links')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function xmlSitemapContext() {
    return $this->get('context')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function maxFileSize() {
    return $this->get('max_filesize')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($smid) {
    $this->set('smid', $smid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setChunks($chunks) {
    $this->set('chunks', $chunks);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinks($links) {
    $this->set('links', $links);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMaxFileSize($max_file_size) {
    $this->set('max_filesize', $max_file_size);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setXmlSitemapContext($context) {
    $this->set('context', $context);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['smid'] = FieldDefinition::create('string')
        ->setLabel(t('ID'))
        ->setDescription(t('The ID of the XmlSitemap entity.'))
        ->setReadOnly(TRUE);
    $fields['uuid'] = FieldDefinition::create('uuid')
        ->setLabel(t('UUID'))
        ->setDescription(t('The UUID of the FooBar entity.'))
        ->setReadOnly(TRUE);
    $fields['context'] = FieldDefinition::create('string')
        ->setLabel(t('Context'))
        ->setDescription(t('The context of the XMLSitemap entity.'))
        ->setReadOnly(FALSE);
    $fields['updated'] = FieldDefinition::create('integer')
        ->setLabel(t('Updated'))
        ->setDescription(t('Check if sitemap is updated'))
        ->setReadOnly(FALSE);
    $fields['links'] = FieldDefinition::create('integer')
        ->setLabel(t('Links'))
        ->setDescription(t('Links number in sitemap'))
        ->setReadOnly(FALSE);
    $fields['chunks'] = FieldDefinition::create('integer')
        ->setLabel(t('Chunks'))
        ->setDescription(t('Chunks number in sitemap'))
        ->setReadOnly(FALSE);
    $fields['max_filesize'] = FieldDefinition::create('integer')
        ->setLabel(t('Maximum File Size'))
        ->setDescription(t('Maximum File Size of sitemap'))
        ->setReadOnly(FALSE);
    return $fields;
  }

}
