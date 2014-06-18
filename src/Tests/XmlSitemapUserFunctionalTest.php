<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapUserFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the generation of user links.
 */
class XmlSitemapUserFunctionalTest extends XmlSitemapTestHelper {

  protected $normal_user;
  protected $accounts = array();
  public static $modules = array('xmlsitemap','user','node');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap user',
      'description' => 'Functional tests for the XML sitemap user module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp($modules = array()) {
    parent::setUp();

    // set xmlsitemap_entity_user state variable to TRUE to add user links into sitemap
    \Drupal::state()->set('xmlsitemap_entity_user', TRUE);
    // Save the user settings before creating the users.
    xmlsitemap_link_bundle_settings_save('user', 'user', array('status' => 1, 'priority' => 0.5));

    // Create the users
    $this->admin_user = $this->drupalCreateUser(array('administer users', 'administer permissions', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));

    // Update the normal user to make its sitemap link visible.
    $account = clone $this->normal_user;
    $account->save();
    //user_save($account, array('access' => 1, 'login' => 1));
  }

  public function testBlockedUser() {
    $this->drupalLogin($this->admin_user);
    $this->assertSitemapLinkVisible('user', $this->normal_user->id());

    // Mark the user as blocked.
    $edit = array(
      'status' => 0,
    );

    // This will pass when http://drupal.org/node/360925 is fixed.
    $this->drupalPostForm('user/' . $this->normal_user->id() . '/edit', $edit, t('Save'));
    $this->assertText('The changes have been saved.');
    $this->assertSitemapLinkNotVisible('user', $this->normal_user->id());
  }

}
