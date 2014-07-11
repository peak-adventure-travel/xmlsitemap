<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the generation of sitemaps.
 */
class XmlSitemapFunctionalTest extends XmlSitemapTestBase {

  public static $modules = array('xmlsitemap', 'path', 'node', 'system');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap interface tests',
      'description' => 'Functional tests for the XML sitemap module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer site configuration', 'administer xmlsitemap'));
  }

  /**
   * Test the sitemap file caching.
   */
  public function testSitemapCaching() {
    $this->drupalLogin($this->admin_user);
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
    $this->drupalLogin($this->admin_user);
    $edit = array('base_url' => '');
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));

    $edit = array('base_url' => 'invalid');
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('Invalid base URL.'));

    $edit = array('base_url' => 'http://example.com/ ');
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
    $this->assertText(t('Invalid base URL.'));

    $edit = array('base_url' => 'http://example.com/');
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings', $edit, t('Save configuration'));
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
    $this->drupalLogin($this->admin_user);
    \Drupal::config('xmlsitemap.settings')->set('generated_last', REQUEST_TIME);
    \Drupal::config('xmlsitemap.settings')->set('rebuild_needed', TRUE);
    \Drupal::config('xmlsitemap.settings')->save();
    $this->assertXMLSitemapProblems(t('The XML sitemap data is out of sync and needs to be completely rebuilt.'));
    $this->clickLink(t('completely rebuilt'));
    $this->assertResponse(200);
    \Drupal::config('xmlsitemap.settings')->set('rebuild_needed', FALSE)->save();
    $this->assertNoXMLSitemapProblems();
    //Test the regenerate flag (and cron hasn't run in a while).
    \Drupal::config('xmlsitemap.settings')->set('regenerate_needed', TRUE);
    \Drupal::config('xmlsitemap.settings')->set('generated_last', REQUEST_TIME - \Drupal::config('xmlsitemap.settings')->get('cron_threshold_warning') - 100);
    \Drupal::config('xmlsitemap.settings')->save();
    $this->assertXMLSitemapProblems(t('The XML cached files are out of date and need to be regenerated. You can run cron manually to regenerate the sitemap files.'));
    $this->clickLink(t('run cron manually'));
    $this->assertResponse(200);
    $this->assertNoXMLSitemapProblems();
  }

}
