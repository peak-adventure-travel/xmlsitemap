<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRobotsTxtIntegrationTest.
 */

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the robots.txt file existance.
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestBase {

  public static $modules = array('xmlsitemap', 'robotstxt');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap robots.txt',
      'description' => 'Integration tests for the XML sitemap and robots.txt module.',
      'group' => 'XML sitemap',
      'dependencies' => array('robotstxt'),
    );
  }

  public function setUp() {
    parent::setUp();

    if (file_exists('robots.txt')) {
      file_unmanaged_move('robots.txt','robots_temp.txt');
    }
  }

  public function testRobotsTxt() {
    // Request the un-clean robots.txt path so this will work in case there is
    // still the robots.txt file in the root directory.
    $this->drupalGet('/robots.txt');
    $this->assertRaw('Sitemap: ' . url('sitemap.xml', array('absolute' => TRUE)));
  }

  public function tearDown() {
    parent::tearDown();

    if (file_exists('robots_temp.txt')) {
      file_unmanaged_move('robots_temp.txt', 'robots.txt');
    }
  }

}
