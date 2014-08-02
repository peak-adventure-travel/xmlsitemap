<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapMenuFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\system\Entity\Menu;

/**
 * Tests the generation of menu links.
 */
class XmlSitemapMenuFunctionalTest extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'xmlsitemap', 'menu_link_content', 'menu_ui');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap menu',
      'description' => 'Functional tests for the XML sitemap menu module.',
      'group' => 'XML sitemap',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page', 'settings' => array(
          // Set proper default options for the page content type.
          'node' => array(
            'options' => array('promote' => FALSE),
            'submitted' => FALSE,
          ),
      )));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // allow anonymous user to administer menu links
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('administer menu');
    $user_role->grantPermission('access content');
    $user_role->save();

    $bundles = $this->entityManager->getAllBundleInfo();
    $this->config->set('xmlsitemap_entity_menu_link_content', TRUE);
    $this->config->set('xmlsitemap_entity_menu', TRUE);
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      $this->config->set('xmlsitemap_entity_menu_link_content_bundle_' . $bundle_id, TRUE);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      $this->config->set('xmlsitemap_entity_menu_bundle_' . $bundle_id, TRUE);
    }
    $this->config->save();

    $this->admin_user = $this->drupalCreateUser(array('administer menu', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Test xmlsitemap settings for menu entity.
   */
  public function testMenuSettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'label' => $this->randomName(),
      'id' => drupal_strtolower($this->randomName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');

    $this->config->set('xmlsitemap_entity_menu_bundle_' . $edit['id'], TRUE);
    $this->config->set('xmlsitemap_entity_menu_link_bundle_' . $edit['id'], TRUE);
    $this->config->save();

    $menu = Menu::load($edit['id']);

    $this->clickLink('Add link');
    $edit = array(
      'title[0][value]' => $this->randomName(),
      'url' => 'node',
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.9,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $bundles = $this->entityManager->getAllBundleInfo();
    $this->config->delete('xmlsitemap_entity_menu_link_content');
    $this->config->delete('xmlsitemap_entity_menu');
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      $this->config->delete('xmlsitemap_entity_menu_link_content_bundle_' . $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      $this->config->delete('xmlsitemap_entity_menu_bundle_' . $bundle_id);
    }

    parent::tearDown();
  }

}
