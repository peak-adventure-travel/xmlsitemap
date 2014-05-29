<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRobotsTxtIntegrationTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\xmlsitemap\Tests\XmlSitemapTestHelper;

/**
 * Tests the robots.txt file existance.
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestHelper {

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap robots.txt',
      'description' => 'Integration tests for the XML sitemap and robots.txt module.',
      'group' => 'XML sitemap',
      'dependencies' => array('robotstxt'),
    );
  }

  public function setUp($modules = array()) {
    $modules[] = 'robotstxt';
    parent::setUp($modules);
  }

  public function testRobotsTxt() {
    // Request the un-clean robots.txt path so this will work in case there is
    // still the robots.txt file in the root directory.
    $this->drupalGet('', array('query' => array('q' => 'robots.txt')));
    $this->assertRaw('Sitemap: ' . url('sitemap.xml', array('absolute' => TRUE)));
  }

}
