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
  public static $modules = array('xmlsitemap', 'user', 'node', 'system');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap user',
      'description' => 'Functional tests for the XML sitemap user module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp($modules = array()) {
    parent::setUp();

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access user profiles');
    $user_role->save();

    // set xmlsitemap_entity_user state variable to TRUE to add user links into sitemap
    \Drupal::state()->set('xmlsitemap_entity_user', TRUE);
    \Drupal::state()->set('xmlsitemap_entity_user_bundle_user', TRUE);
    // Save the user settings before creating the users.
    xmlsitemap_link_bundle_settings_save('user', 'user', array('status' => 0, 'priority' => 0.5));

    // Create the users
    $this->admin_user = $this->drupalCreateUser(array('administer users', 'administer permissions', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));

    // Update the normal user to make its sitemap link visible.
    $account = clone $this->normal_user;
    $account->save();
  }

  public function testBlockedUser() {
    $this->drupalLogin($this->admin_user);
    $this->assertSitemapLinkNotVisible('user', $this->normal_user->id());

    // Mark the user as blocked.
    $edit = array(
      'xmlsitemap[status]' => 1,
    );

    // This will pass when http://drupal.org/node/360925 is fixed.
    $this->drupalPostForm('user/' . $this->normal_user->id() . '/edit', $edit, t('Save'));
    $this->assertText('The changes have been saved.');
    $this->assertSitemapLinkVisible('user', $this->normal_user->id());
  }

}
