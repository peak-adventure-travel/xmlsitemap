<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapNodeFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the generation of user links.
 */
class XmlSitemapNodeFunctionalTest extends XmlSitemapTestHelper {

  public static $modules = array('xmlsitemap', 'node');
  protected $normal_user;
  protected $nodes = array();

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap node',
      'description' => 'Functional tests for the XML sitemap module node entity.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'bypass node access', 'administer content types', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('create page content', 'edit any page content', 'access content', 'view own unpublished content'));
    xmlsitemap_link_bundle_settings_save('node', 'page', array('status' => 1, 'priority' => 0.5));
  }

  public function testNodeSettings() {
    $body_field = 'body[' . LanguageInterface::LANGCODE_NOT_SPECIFIED . '][0][value]';

    $node = $this->drupalCreateNode(array('status' => FALSE, 'uid' => $this->normal_user->id()));
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 0, 'status' => 1, 'priority' => 0.5, 'status_override' => 0, 'priority_override' => 0));

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoField('xmlsitemap[status]');
    $this->assertNoField('xmlsitemap[priority]');

    $edit = array(
      'title' => 'Test node title',
      $body_field => 'Test node body',
    );
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 0, 'status' => 1, 'priority' => 0.5, 'status_override' => 0, 'priority_override' => 0));

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');

    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => 0.9,
      'status' => TRUE,
    );
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 0, 'priority' => 0.9, 'status_override' => 1, 'priority_override' => 1));

    $edit = array(
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
      'status' => FALSE,
    );
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 0, 'status' => 1, 'priority' => 0.5, 'status_override' => 0, 'priority_override' => 0));
  }

  /**
   * Test the content type settings.
   */
  public function testTypeSettings() {
    $this->drupalLogin($this->admin_user);

    $node_old = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node_old->id(), array('status' => 1, 'priority' => 0.5));

    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => '0.0',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertText('The content type Basic page has been updated.');

    $node = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node->id(), array('status' => 0, 'priority' => 0.0));
    $this->assertSitemapLinkValues('node', $node_old->id(), array('status' => 0, 'priority' => 0.0));

    $edit = array(
      'type' => 'page2',
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => '0.5',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertText('Changed the content type of 2 posts from page to page2.');
    $this->assertText('The content type Basic page has been updated.');

    $this->assertSitemapLinkValues('node', $node->id(), array('subtype' => 'page2', 'status' => 1, 'priority' => 0.5));
    $this->assertSitemapLinkValues('node', $node_old->id(), array('subtype' => 'page2', 'status' => 1, 'priority' => 0.5));
    $this->assertEqual(count(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page'))), 0);
    $this->assertEqual(count(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page2'))), 2);

    $this->drupalPost('admin/structure/types/manage/page2/delete', array(), t('Delete'));
    $this->assertText('The content type Basic page has been deleted.');
    $this->assertFalse(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page2')), 'Nodes with deleted node type removed from {xmlsitemap}.');
  }

  /**
   * Test the import of old nodes via cron.
   */
  public function testCron() {
    $limit = 5;
    \Drupal::config('xmlsitemap.settings')->set('batch_limit', $limit);

    $nodes = array();
    for ($i = 1; $i <= ($limit + 1); $i++) {
      $node = $this->drupalCreateNode();
      array_push($nodes, $node);
      // Need to delay by one second so the nodes don't all have the same
      // timestamp.
      sleep(1);
    }

    // Clear all the node link data so we can emulate 'old' nodes.
    db_delete('xmlsitemap')
        ->condition('type', 'node')
        ->execute();

    // Run cron to import old nodes.
    xmlsitemap_node_cron();

    for ($i = 1; $i <= ($limit + 1); $i++) {
      $node = array_pop($nodes);
      if ($i <= $limit) {
        // The first $limit nodes should be inserted.
        $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 1, 'lastmod' => $node->changed));
      }
      else {
        // Any beyond $limit should not be in the sitemap.
        $this->assertNoSitemapLink(array('type' => 'node', 'id' => $node->id()));
      }
    }
  }

}