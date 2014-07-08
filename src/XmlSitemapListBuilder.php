<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapListBuilder.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of XmlSitemap.
 */
class XmlSitemapListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('XmlSitemap');
    if (\Drupal::moduleHandler()->moduleExists('language') && \Drupal::moduleHandler()->moduleExists('config_translation')) {
      $header['language'] = $this->t('Language');
    }
    $header['id'] = $this->t('Sitemap ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    if (\Drupal::moduleHandler()->moduleExists('language') && \Drupal::moduleHandler()->moduleExists('config_translation')) {
      if (isset($entity->context['language'])) {
        $language = \Drupal::languageManager()->getLanguage($entity->context['language']);
        $row['language'] = $language->getName();
      }
      else {
        $row['language'] = $this->t('Undefined');
      }
    }
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (isset($operations['translate'])) {
      unset($operations['translate']);
    }
    return $operations;
  }

}
