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
class XmlSitemapTaxonomyFunctionalTest extends XmlSitemapTestBase {

  public static $modules = array('taxonomy', 'xmlsitemap');
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

    $this->config->set('xmlsitemap_entity_taxonomy_vocabulary', TRUE);
    $this->config->set('xmlsitemap_entity_taxonomy_vocabulary_bundle_taxonomy_vocabulary', TRUE);
    $this->config->save();

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('administer taxonomy');
    $user_role->save();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  public function testTaxonomySettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'name' => $this->randomName(),
      'vid' => drupal_strtolower($this->randomName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');
    $this->assertText("Created new vocabulary {$edit['name']}.");

    $vocabulary = taxonomy_vocabulary_load($edit['vid']);

    $edit = array(
      'name' => $this->randomName(),
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm("admin/structure/taxonomy/manage/{$vocabulary->id()}", $edit, 'Save');
  }

}
