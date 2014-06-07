<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapWriter.
 */

namespace Drupal\xmlsitemap;

/**
 * Extended class for writing XML sitemap files.
 */
class XmlSitemapWriter extends \XMLWriter {

  protected $uri = NULL;
  protected $sitemapElementCount = 0;
  protected $linkCountFlush = 500;
  protected $sitemap = NULL;
  protected $sitemap_page = NULL;
  protected $rootElement = 'urlset';

  /**
   * Constructor.
   *
   * @param $sitemap
   *   The sitemap array.
   * @param $page
   *   The current page of the sitemap being generated.
   */
  function __construct(XmlSitemapInterface $sitemap, $page) {
    $this->sitemap = $sitemap;
    $this->sitemap_page = $page;
    $this->uri = xmlsitemap_sitemap_get_file($sitemap, $page);
    $this->openUri($this->uri);
  }

  public function openUri($uri) {
    $return = parent::openUri($uri);
    if (!$return) {
      throw new XmlSitemapGenerationException(t('Could not open file @file for writing.', array('@file' => $uri)));
    }
    return $return;
  }

  public function startDocument($version = '1.0', $encoding = 'UTF-8', $standalone = NULL) {
    $this->setIndent(FALSE);
    $result = parent::startDocument($version, $encoding);
    if (!$result) {
      throw new XmlSitemapGenerationException(t('Unknown error occurred while writing to file @file.', array('@file' => $this->uri)));
    }
    if (\Drupal::config('xmlsitemap.settings')->get('xsl')) {
      $this->writeXSL();
    }
    $this->startElement($this->rootElement, TRUE);
    return $result;
  }

  /**
   * Add the XML stylesheet to the XML page.
   */
  public function writeXSL() {
    $this->writePi('xml-stylesheet', 'type="text/xsl" href="' . url(NULL, array('absolute' => TRUE)) . 'sitemap.xsl' . '"');
    $this->writeRaw(PHP_EOL);
  }

  /**
   * Return an array of attributes for the root element of the XML.
   */
  public function getRootAttributes() {
    $attributes['xmlns'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    if (\Drupal::state()->get('developer_mode')) {
      $attributes['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
      $attributes['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
    }
    return $attributes;
  }

  public function generateXML() {
    return xmlsitemap_generate_chunk($this->sitemap, $this, $this->sitemap_page);
  }

  public function startElement($name, $root = FALSE) {
    parent::startElement($name);

    if ($root) {
      foreach ($this->getRootAttributes() as $name => $value) {
        $this->writeAttribute($name, $value);
      }
      $this->writeRaw(PHP_EOL);
    }
  }

  /**
   * Write an full XML sitemap element tag.
   *
   * @param $name
   *   The element name.
   * @param $element
   *   An array of the elements properties and values.
   */
  public function writeSitemapElement($name, array &$element) {
    $this->writeElement($name, $element);
    $this->writeRaw(PHP_EOL);

    // After a certain number of elements have been added, flush the buffer
    // to the output file.
    $this->sitemapElementCount++;
    if (($this->sitemapElementCount % $this->linkCountFlush) == 0) {
      $this->flush();
    }
  }

  /**
   * Write full element tag including support for nested elements.
   *
   * @param $name
   *   The element name.
   * @param $content
   *   The element contents or an array of the elements' sub-elements.
   */
  public function writeElement($name, $content = '') {
    if (is_array($content)) {
      $this->startElement($name);
      foreach ($content as $sub_name => $sub_content) {
        $this->writeElement($sub_name, $sub_content);
      }
      $this->endElement();
    }
    else {
      parent::writeElement($name, $content);
    }
  }

  public function getURI() {
    return $this->uri;
  }

  public function getSitemapElementCount() {
    return $this->sitemapElementCount;
  }

  public function endDocument() {
    $return = parent::endDocument();

    if (!$return) {
      throw new XmlSitemapGenerationException(t('Unknown error occurred while writing to file @file.', array('@file' => $this->uri)));
    }

    //if (xmlsitemap_var('gz')) {
    //  $file_gz = $file . '.gz';
    //  file_put_contents($file_gz, gzencode(file_get_contents($file), 9));
    //}

    if (!filesize($this->uri)) {
      throw new XmlSitemapGenerationException(t('Generated @file resulted in an empty file.', array('@file' => $this->uri)));
    }

    return $return;
  }

}
