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
class XmlSitemapMenuFunctionalTest extends XmlSitemapTestHelper {

  protected $normal_user;
  protected $menu_items = array();
  public static $modules = array('node','xmlsitemap', 'menu_link', 'menu_ui');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap menu',
      'description' => 'Functional tests for the XML sitemap menu module.',
      'group' => 'XML sitemap',
    );
  }

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

    $bundles = \Drupal::entityManager()->getAllBundleInfo();
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu_link', TRUE);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu', TRUE);
    foreach ($bundles['menu_link'] as $bundle_id => $bundle) {
      \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu_link_bundle_' . $bundle_id, TRUE);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu_bundle_' . $bundle_id, TRUE);
    }
    \Drupal::config('xmlsitemap.settings')->save();

    $this->admin_user = $this->drupalCreateUser(array('administer menu', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  public function testMenuSettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'label' => $this->randomName(),
      'id' => drupal_strtolower($this->randomName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');

    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu_bundle_' . $edit['id'], TRUE);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_menu_link_bundle_' . $edit['id'], TRUE);
    \Drupal::config('xmlsitemap.settings')->save();

    $menu = Menu::load($edit['id']);

    $this->clickLink('Add link');
    $edit = array(
      'link_title' => $this->randomName(),
      'link_path' => 'node',
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  public function tearDown() {
    $bundles = \Drupal::entityManager()->getAllBundleInfo();
    \Drupal::config('xmlsitemap.settings')->delete('xmlsitemap_entity_menu_link');
    \Drupal::config('xmlsitemap.settings')->delete('xmlsitemap_entity_menu');
    foreach ($bundles['menu_link'] as $bundle_id => $bundle) {
      \Drupal::config('xmlsitemap.settings')->delete('xmlsitemap_entity_menu_link_bundle_' . $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      \Drupal::config('xmlsitemap.settings')->delete('xmlsitemap_entity_menu_bundle_' . $bundle_id);
    }

    parent::tearDown();
  }

}
