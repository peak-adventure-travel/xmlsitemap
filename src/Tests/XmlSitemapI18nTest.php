<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapI18nTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

class XMLSitemapI18nTest extends XMLSitemapI18nWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap i18n tests',
      'description' => 'Functional and integration tests for the XML sitemap and internationalization modules.',
      'group' => 'XML sitemap',
      'dependencies' => array('i18n', 'disable-this-test-for-now'),
    );
  }

  public function testLanguageSelection() {
    // Create our three different language nodes.
    $node = $this->addSitemapLink(array('type' => 'node', 'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $node_en = $this->addSitemapLink(array('type' => 'node', 'language' => 'en'));
    $node_fr = $this->addSitemapLink(array('type' => 'node', 'language' => 'fr'));

    // Create three non-node language nodes.
    $link = $this->addSitemapLink(array('language' => LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $link_en = $this->addSitemapLink(array('language' => 'en'));
    $link_fr = $this->addSitemapLink(array('language' => 'fr'));

    variable_set('i18n_selection_mode', 'off');
    $this->regenerateSitemap();
    $this->drupalGetSitemap(array('language' => 'en'));
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);
    $this->drupalGetSitemap(array('language' => 'fr'));
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);

    variable_set('i18n_selection_mode', 'simple');
    $this->regenerateSitemap();
    $this->drupalGetSitemap(array('language' => 'en'));
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGetSitemap(array('language' => 'fr'));
    $this->assertRawSitemapLinks($node, $node_fr, $link, $link_fr);
    $this->assertNoRawSitemapLinks($node_en, $link_en);

    variable_set('i18n_selection_mode', 'mixed');
    $this->regenerateSitemap();
    $this->drupalGetSitemap(array('language' => 'en'));
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGetSitemap(array('language' => 'fr'));
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);

    variable_set('i18n_selection_mode', 'default');
    $this->regenerateSitemap();
    $this->drupalGetSitemap(array('language' => 'en'));
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGetSitemap(array('language' => 'fr'));
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);

    // With strict mode, the language neutral node should not be found, but the
    // language neutral non-node should be.
    variable_set('i18n_selection_mode', 'strict');
    $this->regenerateSitemap();
    $this->drupalGetSitemap(array('language' => 'en'));
    $this->assertRawSitemapLinks($node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node, $node_fr, $link_fr);
    $this->drupalGetSitemap(array('language' => 'fr'));
    $this->assertRawSitemapLinks($node_fr, $link, $link_fr);
    $this->assertNoRawSitemapLinks($node, $node_en, $link_en);
  }
}
