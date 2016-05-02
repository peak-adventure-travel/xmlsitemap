<?php

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the generation of multilingual nodes.
 *
 * @group xmlsitemap
 */
class XmlSitemapMultilingualNodeTest extends XmlSitemapMultilingualTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'content_translation', 'node', 'locale', 'config_translation', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Basic page',
      'settings' => array(
          // Set proper default options for the page content type.
        'node' => array(
          'options' => array('promote' => FALSE),
          'submitted' => FALSE,
        ), )));
          $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    $this->admin_user = $this->drupalCreateUser(array('administer nodes', 'administer languages', 'administer content types', 'access administration pages', 'create page content', 'edit own page content'));
    $this->drupalLogin($this->admin_user);

    xmlsitemap_link_bundle_enable('node', 'article');

    xmlsitemap_link_bundle_enable('node', 'page');

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

  /**
   * Test language for sitemap node links.
   */
  public function testNodeLanguageData() {
    $this->drupalLogin($this->admin_user);
    $node = $this->drupalCreateNode(array());

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('langcode' => 'en'), t('Save and keep published'));
    $link = $this->assertSitemapLink('node', $node->id(), array('status' => 0, 'access' => 1));
    $this->assertIdentical($link['language'], 'en');

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('langcode' => 'fr'), t('Save and keep published'));
    $link = $this->assertSitemapLink('node', $node->id(), array('status' => 0, 'access' => 1));
    $this->assertIdentical($link['language'], 'fr');
  }

}
