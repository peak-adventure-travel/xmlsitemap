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
 *       "add" = "Drupal\xmlsitemap\Entity\Form\XmlSitemapFormController",
 *       "edit" = "Drupal\xmlsitemap\Entity\Form\XmlSitemapFormController",
 *       "delete" = "Drupal\xmlsitemap\Entity\Form\XmlSitemapDeleteForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "xmlsitemap_sitemap",
 *   admin_permission = "administer xmlsitemap",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "smid",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "xmlsitemap.admin_edit",
 *     "admin-form" = "xmlsitemap.admin_settings",
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
