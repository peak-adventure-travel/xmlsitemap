<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Entity\Controller\XmlSitemapListBuilder.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for xmlsitemap entity.
 */
class XmlSitemapListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('XmlSitemap ID');
    $header['last_updated'] = t('LAST UPDATED');
    $header['links'] = t('Links');
    $header['pages'] = t('Pages');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\xmlsitemap\Entity\XmlSitemap */
    $row['id'] = $entity->id();
    $row['last_updated'] = t('Updated');
    $row['links'] = $entity->links();
    $row['pages'] = $entity->chunks();
    return $row;
    $row['label'] = l($this->getLabel($entity), 'xmlsitemap/' . $entity->id());
    return $row + parent::buildRow($entity);
  }

}
