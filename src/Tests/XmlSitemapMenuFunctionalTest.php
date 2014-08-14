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
  public static $modules = array('node', 'xmlsitemap', 'menu_link_content', 'menu_ui', 'system');

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
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu', $bundle_id);
    }

    $this->admin_user = $this->drupalCreateUser(array('administer menu', 'administer xmlsitemap', 'access administration pages'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Test xmlsitemap settings for menu entity.
   */
  public function testMenuSettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'label' => $this->randomMachineName(),
      'id' => drupal_strtolower($this->randomMachineName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');

    xmlsitemap_link_bundle_settings_save('menu', $edit['id'], array('status' => 0, 'priority' => 0.5, 'changefreq' => 0));

    $this->drupalGet('admin/structure/menu/manage/' . $edit['id']);

    $menu_id = $edit['id'];
    $this->clickLink('Add link');
    $edit = array(
      'url' => 'node',
      'title[0][value]' => $this->randomMachineName(),
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => FALSE,
      'menu_parent' => $menu_id . ':',
      'weight[0][value]' => 0,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $bundles = $this->entityManager->getAllBundleInfo();
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu', $bundle_id);
    }

    parent::tearDown();
  }

}
