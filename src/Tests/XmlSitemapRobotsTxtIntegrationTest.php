<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Url;

/**
 * Tests the robots.txt file existence.
 *
 * @group xmlsitemap
 * @dependencies robotstxt
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['robotstxt'];

  /**
   * Test if sitemap link is included in robots.txt file.
   */
  public function testRobotsTxt() {
    // Move the robots.txt file shipped by core out of the way for the duration
    // of this test.
    $new_path = FALSE;
    if (file_exists(DRUPAL_ROOT . '/robots.txt')) {
      $new_path = file_unmanaged_move(DRUPAL_ROOT . '/robots.txt', DRUPAL_ROOT . '/robots.txt.tmp');
    }

    $this->drupalGet('robots.txt');
    $this->assertRaw('Sitemap: ' . Url::fromRoute('xmlsitemap.sitemap_xml', [], ['absolute' => TRUE])->toString());

    // Put back the original robots.txt file.
    if ($new_path) {
      file_unmanaged_move($new_path, DRUPAL_ROOT . '/robots.txt');
    }
  }

}
