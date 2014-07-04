<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapTaxonomyFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the generation of taxonomy links.
 */
class XmlSitemapTaxonomyFunctionalTest extends XmlSitemapTestHelper {

  public static $modules = array('taxonomy', 'xmlsitemap');
  protected $normal_user;
  protected $nodes = array();

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap taxonomy',
      'description' => 'Functional tests for the XML sitemap module taxonomy entity.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  public function testTaxonomySettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'name' => $this->randomName(),
      'machine_name' => drupal_strtolower($this->randomName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');
    $this->assertText("Created new vocabulary {$edit['name']}.");
    $vocabulary = taxonomy_vocabulary_machine_name_load($edit['machine_name']);

    $edit = array(
      'name' => $this->randomName(),
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm("admin/structure/taxonomy/{$vocabulary->machine_name}/add", $edit, 'Save');
  }

}
