<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapIndexWriter.
 */

namespace Drupal\xmlsitemap;

/**
 * Extended class for writing XML sitemap indexes.
 */
class XmlSitemapIndexWriter extends XmlSitemapWriter {

  protected $rootElement = 'sitemapindex';

  function __construct(XmlSitemapInterface $sitemap, $page = 'index') {
    parent::__construct($sitemap, 'index');
  }

  public function getRootAttributes() {
    $attributes['xmlns'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    if (\Drupal::state()->get('developer_mode')) {
      $attributes['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
      $attributes['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd';
    }
    return $attributes;
  }

  public function generateXML() {
    $lastmod_format = \Drupal::config('xmlsitemap.settings')->get('lastmod_format');

    $url_options = $this->sitemap->uri['options'];
    $url_options += array(
      'absolute' => TRUE,
      'base_url' => variable_get('xmlsitemap_base_url', $GLOBALS['base_url']),
      'language' => language_default(),
      'alias' => TRUE,
    );

    for ($i = 1; $i <= $this->sitemap->chunks; $i++) {
      $url_options['query']['page'] = $i;
      $element = array(
        'loc' => url('sitemap.xml', $url_options),
        // @todo Use the actual lastmod value of the chunk file.
        'lastmod' => gmdate($lastmod_format, REQUEST_TIME),
      );
      $this->writeSitemapElement('sitemap', $element);
    }
  }

}
