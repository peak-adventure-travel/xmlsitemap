<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRandomEntityFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the generation of a random content entity links.
 */
class XmlSitemapEntityFunctionalTest extends XmlSitemapTestBase {

  public static $modules = array('system', 'xmlsitemap', 'entity_test');
  protected $normal_user;
  protected $config;

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap random entity',
      'description' => 'Functional tests for the XML sitemap random entity links.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->config = \Drupal::configFactory()->get('xmlsitemap.settings');

    $this->admin_user = $this->drupalCreateUser(array('administer entity_test content', 'administer xmlsitemap'));

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('view test entity');
    $user_role->grantPermission('view test entity translations');
    $user_role->save();
  }

  public function testEntitiesSettingsForms() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/entities/settings');
    $this->assertResponse(200);
    $this->assertField('entity_types[entity_test]');
    $this->assertField('settings[entity_test][entity_test][settings][bundle]');
    $edit = array(
      'entity_types[entity_test]' => 1,
      'settings[entity_test][entity_test][settings][bundle]' => 1
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The configuration options have been saved.'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test',
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 0, 'priority' => 0.5));
  }

  public function testEntityLinkBundleSettingsForm() {
    $this->config->set('xmlsitemap_entity_entity_test', 1);
    $this->config->set('xmlsitemap_entity_entity_test_bundle_entity_test', 1);
    $this->config->save();
    $this->drupalLogin($this->admin_user);
    // set priority and inclusion for entity_test - entity_test
    $this->drupalGet('admin/config/search/xmlsitemap/settings/entity_test/entity_test');
    $this->assertResponse(200);
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => 0.3
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test'
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 0, 'priority' => 0.3));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertNoText($entity->url());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test');

    $edit = array(
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.6
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings/entity_test/entity_test', $edit, t('Save configuration'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test'
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 1, 'priority' => 0.6));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertText($entity->url());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test', $id);
  }

}
