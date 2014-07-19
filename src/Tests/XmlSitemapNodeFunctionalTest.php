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
class XmlSitemapNodeFunctionalTest extends XmlSitemapTestBase {

  public static $modules = array('node', 'xmlsitemap');
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

    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'bypass node access', 'administer content types', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('create page content', 'edit any page content', 'access content', 'view own unpublished content'));

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access content');
    $user_role->save();

    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node', 1);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node_bundle_article', 1);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node_bundle_page', 1);
    \Drupal::config('xmlsitemap.settings')->save();
    xmlsitemap_link_bundle_settings_save('node', 'page', array('status' => 1, 'priority' => 0.6));
  }

  public function testNodeSettings() {
    $node = $this->drupalCreateNode(array('publish' => 0, 'uid' => $this->normal_user->id()));
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 0, 'priority' => 0.5, 'status_override' => 0, 'priority_override' => 0));

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoField('xmlsitemap[status]');
    $this->assertNoField('xmlsitemap[priority]');

    $edit = array(
      'title[0][value]' => 'Test node title',
      'body[0][value]' => 'Test node body'
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 1, 'priority' => 0.6, 'status_override' => 0, 'priority_override' => 0));

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');

    $edit = array(
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.9
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 1, 'priority' => 0.9, 'status_override' => 1, 'priority_override' => 1));

    $edit = array(
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default'
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertText('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 1, 'priority' => 0.6, 'status_override' => 0, 'priority_override' => 0));
  }

  /**
   * Test the content type settings.
   */
  public function testTypeSettings() {
    $this->drupalLogin($this->admin_user);

    $node_old = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node_old->id(), array('status' => 0, 'priority' => 0.5));

    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => '0.0',
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings/node/page', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $node = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node->id(), array('status' => 0, 'priority' => 0.5));
    $this->assertSitemapLinkValues('node', $node_old->id(), array('status' => 0, 'priority' => 0.0));

    $edit = array(
      'type' => 'page2'
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertText('Changed the content type of 2 posts from page to page2.');
    $this->assertText('The content type Basic page has been updated.');

    $this->assertSitemapLinkValues('node', $node->id(), array('subtype' => 'page2', 'status' => 0, 'priority' => 0.5));
    $this->assertSitemapLinkValues('node', $node_old->id(), array('subtype' => 'page2', 'status' => 0, 'priority' => 0.0));
    $this->assertEqual(count(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page'))), 0);
    $this->assertEqual(count(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page2'))), 2);

    // delete all pages in order to allow content type deletion
    $node->delete();
    $node_old->delete();

    $this->drupalPostForm('admin/structure/types/manage/page2/delete', array(), t('Delete'));
    $this->assertText('The content type Basic page has been deleted.');
    $this->assertFalse(xmlsitemap_link_load_multiple(array('type' => 'node', 'subtype' => 'page2')), 'Nodes with deleted node type removed from {xmlsitemap}.');
  }

  /**
   * Test the import of old nodes via cron.
   */
  public function testCron() {
    $limit = 5;
    \Drupal::config('xmlsitemap.settings')->set('batch_limit', $limit)->save();
    \Drupal::state()->set('regenerate_needed', TRUE);
    
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
    xmlsitemap_cron();

    for ($i = 1; $i <= ($limit + 1); $i++) {
      $node = array_pop($nodes);
      if ($i != 1) {
        // The first $limit nodes should be inserted.
        $this->assertSitemapLinkValues('node', $node->id(), array('access' => 1, 'status' => 0));
      }
      else {
        // Any beyond $limit should not be in the sitemap.
        $this->assertNoSitemapLink(array('type' => 'node', 'id' => $node->id()));
      }
    }
  }

}
