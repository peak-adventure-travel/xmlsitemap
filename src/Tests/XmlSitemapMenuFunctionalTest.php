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
  public static $modules = array('xmlsitemap', 'menu_link', 'menu_ui');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap menu',
      'description' => 'Functional tests for the XML sitemap menu module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer menu', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  public function testMenuSettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'title' => $this->randomName(),
      'menu_name' => drupal_strtolower($this->randomName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');

    $menu = Menu::load($edit['menu_name']);

    $this->clickLink('Add link');
    $edit = array(
      'link_title' => $this->randomName(),
      'link_path' => 'node',
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

}
