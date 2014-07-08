<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapI18nWebTestCase.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Common base test class for XML sitemap internationalization tests.
 */
class XmlSitemapI18nWebTestCase extends XmlSitemapTestHelper {

  protected $admin_user;
  public static $modules = array('language', 'xmlsitemap', 'locale');

  /**
   * Set up an administrative user account and testing keys.
   */
  public function setUp() {
    // Call parent::setUp() allowing test cases to pass further modules.
    parent::setUp();

    // Add predefined language and reset the locale cache.
    /* require_once DRUPAL_ROOT . '/includes/locale.inc';
      locale_add_language('fr', NULL, NULL, LANGUAGE_LTR, '', 'fr');
      drupal_language_initialize();
      variable_set('language_negotiation', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX);
     */

    // Create the two different language-context sitemaps.
    $previous_sitemaps = entity_load_multiple('xmlsitemap');
    foreach ($previous_sitemaps as $previous_sitemap) {
      $previous_sitemap->delete();
    }

    $sitemap = \Drupal::entityManager()->getStorage('xmlsitemap')->create(array());
    $sitemap->context = array('language' => 'en');
    xmlsitemap_sitemap_save($sitemap);
    $sitemap = \Drupal::entityManager()->getStorage('xmlsitemap')->create(array());
    $sitemap->context = array('language' => 'fr');
    xmlsitemap_sitemap_save($sitemap);
  }
  
  public function testMe() {
    
  }

}
