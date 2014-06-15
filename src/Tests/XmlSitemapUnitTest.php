<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapUnitTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\xmlsitemap\Tests\XmlSitemapTestHelper;

/**
 * Unit testing class for xmlsitemap.
 */
class XmlSitemapUnitTest extends XmlSitemapTestHelper {

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap unit tests',
      'description' => 'Unit tests for the XML sitemap module.',
      'group' => 'XML sitemap',
    );
  }

  public function testAssertFlag() {
    \Drupal::config('xmlsitemap.settings')->set('rebuild_needed', TRUE);
    $this->assertTrue(xmlsitemap_var('rebuild_needed'));
    $this->assertTrue($this->assertFlag('rebuild_needed', TRUE, FALSE));
    $this->assertTrue(xmlsitemap_var('rebuild_needed'));
    $this->assertTrue($this->assertFlag('rebuild_needed', TRUE, TRUE));
    $this->assertFalse(xmlsitemap_var('rebuild_needed'));
    $this->assertTrue($this->assertFlag('rebuild_needed', FALSE, FALSE));
    $this->assertFalse(xmlsitemap_var('rebuild_needed'));
  }

  /**
   * Tests for xmlsitemap_get_changefreq().
   */
  public function testGetChangefreq() {
    // The test values.
    $values = array(
      0,
      mt_rand(1, XMLSITEMAP_FREQUENCY_ALWAYS),
      mt_rand(XMLSITEMAP_FREQUENCY_ALWAYS + 1, XMLSITEMAP_FREQUENCY_HOURLY),
      mt_rand(XMLSITEMAP_FREQUENCY_HOURLY + 1, XMLSITEMAP_FREQUENCY_DAILY),
      mt_rand(XMLSITEMAP_FREQUENCY_DAILY + 1, XMLSITEMAP_FREQUENCY_WEEKLY),
      mt_rand(XMLSITEMAP_FREQUENCY_WEEKLY + 1, XMLSITEMAP_FREQUENCY_MONTHLY),
      mt_rand(XMLSITEMAP_FREQUENCY_MONTHLY + 1, XMLSITEMAP_FREQUENCY_YEARLY),
      mt_rand(XMLSITEMAP_FREQUENCY_YEARLY + 1, mt_getrandmax()),
    );

    // The expected values.
    $expected = array(
      FALSE,
      'always',
      'hourly',
      'daily',
      'weekly',
      'monthly',
      'yearly',
      'never',
    );

    foreach ($values as $i => $value) {
      $actual = xmlsitemap_get_changefreq($value);
      $this->assertIdentical($actual, $expected[$i]);
    }
  }

  /**
   * Tests for xmlsitemap_get_chunk_count().
   */
  public function testGetChunkCount() {
    // Set a low chunk size for testing.
    \Drupal::config('xmlsitemap.settings')->set('chunk_size', 4);

    // Make the total number of links just equal to the chunk size.
    $count = db_query("SELECT COUNT(id) FROM {xmlsitemap}")->fetchField();
    for ($i = $count; $i < 4; $i++) {
      $this->addSitemapLink();
      $this->assertEqual(xmlsitemap_get_chunk_count(TRUE), 1);
    }
    $this->assertEqual(db_query("SELECT COUNT(id) FROM {xmlsitemap}")->fetchField(), 4);

    // Add a disabled link, should not change the chunk count.
    $this->addSitemapLink(array('status' => FALSE));
    $this->assertEqual(xmlsitemap_get_chunk_count(TRUE), 1);

    // Add a visible link, should finally bump up the chunk count.
    $this->addSitemapLink();
    $this->assertEqual(xmlsitemap_get_chunk_count(TRUE), 2);

    // Change all links to disabled. The chunk count should be 1 not 0.
    db_query("UPDATE {xmlsitemap} SET status = 0");
    $this->assertEqual(xmlsitemap_get_chunk_count(TRUE), 1);
    $this->assertEqual(xmlsitemap_get_link_count(), 0);

    // Delete all links. The chunk count should be 1 not 0.
    db_query("DELETE FROM {xmlsitemap}");
    $this->assertEqual(db_query("SELECT COUNT(id) FROM {xmlsitemap}")->fetchField(), 0);
    $this->assertEqual(xmlsitemap_get_chunk_count(TRUE), 1);
  }

  //function testGetChunkFile() {
  //}
  //
  //function testGetChunkSize() {
  //}
  //
  //function testGetLinkCount() {
  //}

  /**
   * Tests for xmlsitemap_calculate_changereq().
   */
  public function testCalculateChangefreq() {
    // The test values.
    $values = array(
      array(),
      array(REQUEST_TIME),
      array(REQUEST_TIME, REQUEST_TIME - 200),
      array(REQUEST_TIME - 200, REQUEST_TIME, REQUEST_TIME - 600),
    );

    // Expected values.
    $expected = array(0, 0, 200, 300);

    foreach ($values as $i => $value) {
      $actual = xmlsitemap_calculate_changefreq($value);
      $this->assertEqual($actual, $expected[$i]);
    }
  }

  /**
   * Test for xmlsitemap_recalculate_changefreq().
   */
  public function testRecalculateChangefreq() {
    // The starting test value.
    $value = array('lastmod' => REQUEST_TIME - 1000, 'changefreq' => 0, 'changecount' => 0);

    // Expected values.
    $expecteds = array(
      array('lastmod' => REQUEST_TIME, 'changefreq' => 1000, 'changecount' => 1),
      array('lastmod' => REQUEST_TIME, 'changefreq' => 500, 'changecount' => 2),
      array('lastmod' => REQUEST_TIME, 'changefreq' => 333, 'changecount' => 3),
    );

    foreach ($expecteds as $expected) {
      xmlsitemap_recalculate_changefreq($value);
      $this->assertEqual($value, $expected);
    }
  }

  /**
   * Tests for xmlsitemap_switch_user and xmlsitemap_restore_user().
   */
  public function testSwitchUser() {
    global $user;

    $original_user = $user;
    $new_user = $this->drupalCreateUser();

    // Switch to a new valid user.
    $this->assertEqual(xmlsitemap_switch_user($new_user), TRUE);
    $this->assertEqual($user->uid, $new_user->uid);

    // Switch again to the anonymous user.
    $this->assertEqual(xmlsitemap_switch_user(0), TRUE);
    $this->assertEqual($user->uid, 0);

    // Switch again to the new user.
    $this->assertEqual(xmlsitemap_switch_user($new_user->uid), TRUE);
    $this->assertEqual($user->uid, $new_user->uid);

    // Test that after two switches the original user was restored.
    $this->assertEqual(xmlsitemap_restore_user(), TRUE);
    $this->assertEqual($user->uid, $original_user->uid);

    // Attempt to switch to the same user.
    $this->assertEqual(xmlsitemap_switch_user($original_user->uid), FALSE);
    $this->assertEqual($user->uid, $original_user->uid);
    $this->assertEqual(xmlsitemap_restore_user(), FALSE);
    $this->assertEqual($user->uid, $original_user->uid);

    // Attempt to switch to an invalid user ID.
    $invalid_uid = db_query("SELECT MAX(uid) FROM {users}")->fetchField() + 100;
    $this->assertEqual(xmlsitemap_switch_user($invalid_uid), FALSE);
    $this->assertEqual($user->uid, $original_user->uid);
    $this->assertEqual(xmlsitemap_restore_user(), FALSE);
    $this->assertEqual($user->uid, $original_user->uid);

    // Attempt user switching when the original user is anonymous.
    $user = drupal_anonymous_user();
    $this->assertEqual(xmlsitemap_switch_user(0), FALSE);
    $this->assertEqual($user->uid, 0);
    $this->assertEqual(xmlsitemap_restore_user(), FALSE);
    $this->assertEqual($user->uid, 0);
  }

  //function testLoadLink() {
  //}

  /**
   * Tests for xmlsitemap_link_save().
   */
  public function testSaveLink() {
    $link = array('type' => 'testing', 'id' => 1, 'loc' => 'testing', 'status' => 1);
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['status'] = 0;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 0.5;
    $link['loc'] = 'new_location';
    $link['status'] = 1;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 0.0;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 0.1;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 1.0;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 1;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', FALSE);

    $link['priority'] = 0;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 0.5;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', TRUE);

    $link['priority'] = 0.5;
    $link['priority_override'] = 0;
    $link['status'] = 1;
    xmlsitemap_link_save($link);
    $this->assertFlag('regenerate_needed', FALSE);
  }

  /**
   * Tests for xmlsitemap_link_delete().
   */
  public function testLinkDelete() {
    // Add our testing data.
    $link1 = $this->addSitemapLink(array('loc' => 'testing1', 'status' => 0));
    $link2 = $this->addSitemapLink(array('loc' => 'testing1', 'status' => 1));
    $link3 = $this->addSitemapLink(array('status' => 0));
    variable_set('xmlsitemap_regenerate_needed', FALSE);

    // Test delete multiple links.
    // Test that the regenerate flag is set when visible links are deleted.
    $deleted = xmlsitemap_link_delete_multiple(array('loc' => 'testing1'));
    $this->assertEqual($deleted, 2);
    $this->assertFalse(xmlsitemap_link_load($link1['type'], $link1['id']));
    $this->assertFalse(xmlsitemap_link_load($link2['type'], $link2['id']));
    $this->assertTrue(xmlsitemap_link_load($link3['type'], $link3['id']));
    $this->assertFlag('regenerate_needed', TRUE);

    $deleted = xmlsitemap_link_delete($link3['type'], $link3['id']);
    $this->assertEqual($deleted, 1);
    $this->assertFalse(xmlsitemap_link_load($link3['type'], $link3['id']));
    $this->assertFlag('regenerate_needed', FALSE);
  }

  /**
   * Tests for xmlsitemap_link_update_multiple().
   */
  public function testUpdateLinks() {
    // Add our testing data.
    $links = array();
    $links[1] = $this->addSitemapLink(array('subtype' => 'group1'));
    $links[2] = $this->addSitemapLink(array('subtype' => 'group1'));
    $links[3] = $this->addSitemapLink(array('subtype' => 'group2'));
    variable_set('xmlsitemap_regenerate_needed', FALSE);
    // id | type    | subtype | language | access | status | priority
    // 1  | testing | group1  | ''       | 1      | 1      | 0.5
    // 2  | testing | group1  | ''       | 1      | 1      | 0.5
    // 3  | testing | group2  | ''       | 1      | 1      | 0.5

    $updated = xmlsitemap_link_update_multiple(array('status' => 0), array('type' => 'testing', 'subtype' => 'group1', 'status_override' => 0));
    $this->assertEqual($updated, 2);
    $this->assertFlag('regenerate_needed', TRUE);
    // id | type    | subtype | language | status | priority
    // 1  | testing | group1  | ''       | 0      | 0.5
    // 2  | testing | group1  | ''       | 0      | 0.5
    // 3  | testing | group2  | ''       | 1      | 0.5

    $updated = xmlsitemap_link_update_multiple(array('priority' => 0.0), array('type' => 'testing', 'subtype' => 'group1', 'priority_override' => 0));
    $this->assertEqual($updated, 2);
    $this->assertFlag('regenerate_needed', FALSE);
    // id | type    | subtype | language | status | priority
    // 1  | testing | group1  | ''       | 0      | 0.0
    // 2  | testing | group1  | ''       | 0      | 0.0
    // 3  | testing | group2  | ''       | 1      | 0.5

    $updated = xmlsitemap_link_update_multiple(array('subtype' => 'group2'), array('type' => 'testing', 'subtype' => 'group1'));
    $this->assertEqual($updated, 2);
    $this->assertFlag('regenerate_needed', FALSE);
    // id | type    | subtype | language | status | priority
    // 1  | testing | group2  | ''       | 0      | 0.0
    // 2  | testing | group2  | ''       | 0      | 0.0
    // 3  | testing | group2  | ''       | 1      | 0.5

    $updated = xmlsitemap_link_update_multiple(array('status' => 1), array('type' => 'testing', 'subtype' => 'group2', 'status_override' => 0, 'status' => 0));
    $this->assertEqual($updated, 2);
    $this->assertFlag('regenerate_needed', TRUE);
    // id | type    | subtype | language | status | priority
    // 1  | testing | group2  | ''       | 1      | 0.0
    // 2  | testing | group2  | ''       | 1      | 0.0
    // 3  | testing | group2  | ''       | 1      | 0.5
  }

  /**
   * Test that duplicate paths are skipped during generation.
   */
  public function testDuplicatePaths() {
    $link1 = $this->addSitemapLink(array('loc' => 'duplicate'));
    $link2 = $this->addSitemapLink(array('loc' => 'duplicate'));
    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertUniqueText('duplicate');
  }

  /**
   * Test that the sitemap will not be genereated before the lifetime expires.
   */
  public function testMinimumLifetime() {
    variable_set('xmlsitemap_minimum_lifetime', 300);
    $this->regenerateSitemap();

    $link = $this->addSitemapLink(array('loc' => 'lifetime-test'));
    $this->cronRun();
    $this->drupalGetSitemap();
    $this->assertResponse(200);
    $this->assertNoRaw('lifetime-test');

    variable_set('xmlsitemap_generated_last', REQUEST_TIME - 400);
    $this->cronRun();
    $this->drupalGetSitemap();
    $this->assertRaw('lifetime-test');

    xmlsitemap_link_delete($link['type'], $link['id']);
    $this->cronRun();
    $this->drupalGetSitemap();
    $this->assertRaw('lifetime-test');

    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertResponse(200);
    $this->assertNoRaw('lifetime-test');
  }

}