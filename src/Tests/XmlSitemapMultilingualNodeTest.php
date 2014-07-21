<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapMultilingualNodeTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the generation of multilingual nodes.
 */
class XmlSitemapMultilingualNodeTest extends XmlSitemapMultilingualTestBase {

  public static $modules = array('xmlsitemap', 'language', 'content_translation', 'node', 'locale', 'config_translation', 'system');
  protected $config;

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap i18n node tests',
      'description' => 'Functional and integration tests for the XML sitemap node and internationalization modules.',
      'group' => 'XML sitemap'
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

    $this->config = \Drupal::configFactory()->get('xmlsitemap.settings');
    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'administer languages', 'administer content types', 'access administration pages', 'create page content', 'edit own page content'));
    $this->drupalLogin($this->admin_user);

    $this->config->set('xmlsitemap_entity_node', 1);
    $this->config->set('xmlsitemap_entity_node_bundle_article', 1);
    $this->config->set('xmlsitemap_entity_node_bundle_page', 1);
    $this->config->save();

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access content');
    $user_role->save();

    // Set "Basic page" content type to use multilingual support.
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), 'Basic page content type has been updated.');
  }

  public function testNodeLanguageData() {
    $this->drupalLogin($this->admin_user);
    $node = $this->drupalCreateNode(array());

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('langcode' => 'en'), t('Save and keep published'));
    $link = $this->assertSitemapLink('node', $node->id());
    $this->assertIdentical($link['language'], 'en');

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('langcode' => 'fr'), t('Save and keep published'));
    $link = $this->assertSitemapLink('node', $node->id());
    $this->assertIdentical($link['language'], 'fr');
  }

}
