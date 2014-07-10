<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapI18nNodeTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\LanguageInterface;

class XmlSitemapI18nNodeTest extends XmlSitemapI18nWebTestCase {

  public static $modules = array('xmlsitemap', 'language', 'content_translation', 'node');

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
    
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node', 1);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node_bundle_article', 1);
    \Drupal::config('xmlsitemap.settings')->set('xmlsitemap_entity_node_bundle_page', 1);
    \Drupal::config('xmlsitemap.settings')->save();

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access content');
    $user_role->save();

    $name = language_get_default_configuration_settings_key('node', 'page');
    //variable_set('language_content_type_page', 1);
    \Drupal::config('language.settings')->set($name, array('language_show' => TRUE, 'langcode' => 'site_default'))->save();
    $this->admin_user = $this->drupalCreateUser(array('administer nodes'));
    $this->drupalLogin($this->admin_user);
  }

  public function testNodeLanguageData() {
    $node = $this->drupalCreateNode(array());

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('language' => 'und'), t('Save'));
    $link = $this->assertSitemapLink('node', $node->id());
    $this->assertIdentical($link['language'], 'en');

    $this->drupalPostForm('node/' . $node->id() . '/edit', array('language' => 'und'), t('Save'));
    $link = $this->assertSitemapLink('node', $node->id());
    $this->assertIdentical($link['language'], 'fr');
  }

}
