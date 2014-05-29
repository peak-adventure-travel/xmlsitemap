<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\xmlsitemap\Tests\XmlSitemapTestHelper;

/**
 * Tests the generation of sitemaps.
 */
class XmlSitemapFunctionalTest extends XmlSitemapTestHelper {

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap interface tests',
      'description' => 'Functional tests for the XML sitemap module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp($modules = array()) {
    $modules[] = 'path';
    parent::setUp($modules);
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer site configuration', 'administer xmlsitemap'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test the sitemap file caching.
   */
  public function testSitemapCaching() {
    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertResponse(200);
    $etag = $this->drupalGetHeader('etag');
    $last_modified = $this->drupalGetHeader('last-modified');
    $this->assertTrue($etag, t('Etag header found.'));
    $this->assertTrue($last_modified, t('Last-modified header found.'));

    $this->drupalGetSitemap(array(), array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(304);
  }

  /**
   * Test base URL functionality.
   */
  public function testBaseURL() {
    $edit = array('xmlsitemap_base_url' => '');
    $this->drupalPost('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('Default base URL field is required.'));

    $edit = array('xmlsitemap_base_url' => 'invalid');
    $this->drupalPost('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('Invalid base URL.'));

    $edit = array('xmlsitemap_base_url' => 'http://example.com/ ');
    $this->drupalPost('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('Invalid base URL.'));

    $edit = array('xmlsitemap_base_url' => 'http://example.com/');
    $this->drupalPost('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->regenerateSitemap();
    $this->drupalGetSitemap(array(), array('base_url' => NULL));
    $this->assertRaw('<loc>http://example.com/</loc>');
  }

  /**
   * Test that configuration problems are reported properly in the status report.
   */
  public function testStatusReport() {
    // Test the rebuild flag.
    // @todo Re-enable these tests once we get a xmlsitemap_test.module.
    //variable_set('xmlsitemap_generated_last', REQUEST_TIME);
    //variable_set('xmlsitemap_rebuild_needed', TRUE);
    //$this->assertXMLSitemapProblems(t('The XML sitemap data is out of sync and needs to be completely rebuilt.'));
    //$this->clickLink(t('completely rebuilt'));
    //$this->assertResponse(200);
    //variable_set('xmlsitemap_rebuild_needed', FALSE);
    //$this->assertNoXMLSitemapProblems();
    // Test the regenerate flag (and cron hasn't run in a while).
    variable_set('xmlsitemap_regenerate_needed', TRUE);
    variable_set('xmlsitemap_generated_last', REQUEST_TIME - variable_get('cron_threshold_warning', 172800) - 100);
    $this->assertXMLSitemapProblems(t('The XML cached files are out of date and need to be regenerated. You can run cron manually to regenerate the sitemap files.'));
    $this->clickLink(t('run cron manually'));
    $this->assertResponse(200);
    $this->assertNoXMLSitemapProblems();

    // Test chunk count > 1000.
    // Test directory not writable.
  }

}
