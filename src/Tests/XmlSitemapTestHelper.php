<?php

/**
 * @file
 * Definition of Drupal\xmlsitemap\Tests\XmlSitemapTestHelper.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Helper test class with some added functions for testing.
 */
class XmlSitemapTestHelper extends WebTestBase {

  public static $modules = array('xmlsitemap');

  protected $admin_user;

  public function setUp() {
    array_unshift(self::$modules, 'xmlsitemap');
    parent::setUp();
  }

  public function tearDown() {
    // Capture any (remaining) watchdog errors.
    $this->assertNoWatchdogErrors();

    parent::tearDown();
  }

  /**
   * Assert the page does not respond with the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   Message to display.
   * @return
   *   Assertion result.
   */
  protected function assertNoResponse($code, $message = '',$group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertFalse($match, $message ? $message : t('HTTP response not expected !code, actual !curl_code', array('!code' => $code, '!curl_code' => $curl_code)), t('Browser'));
  }

  /**
   * Check the files directory is created (massive fails if not done).
   *
   * @todo This can be removed when http://drupal.org/node/654752 is fixed.
   */
  protected function checkFilesDirectory() {
    if (!xmlsitemap_check_directory()) {
      $this->fail(t('Sitemap directory was found and writable for testing.'));
    }
  }

  /**
   * Retrieves an XML sitemap.
   *
   * @param $context
   *   An optional array of the XML sitemap's context.
   * @param $options
   *   Options to be forwarded to url(). These values will be merged with, but
   *   always override $sitemap->uri['options'].
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   * @return
   *   The retrieved HTML string, also available as $this->drupalGetContent()
   */
  protected function drupalGetSitemap(array $context = array(), array $options = array(), array $headers = array()) {
    $sitemap = xmlsitemap_sitemap_load_by_context($context);
    if (!$sitemap) {
      return $this->fail('Could not load sitemap by context.');
    }
    $uri = xmlsitemap_sitemap_uri($sitemap);
    return $this->drupalGet($uri['path'], $options + $uri['options'], $headers);
  }

  /**
   * Regenerate the sitemap by setting the regenerate flag and running cron.
   */
  protected function regenerateSitemap() {
    \Drupal::config('xmlsitemap.settings')->set('regenerate_needed', TRUE);
    \Drupal::config('xmlsitemap.settings')->set('generated_last', 0);
    $this->cronRun();
    $this->assertTrue(\Drupal::config('xmlsitemap.settings')->get('generated_last') && !\Drupal::config('xmlsitemap.settings')->get('regenerate_needed'), t('XML sitemaps regenerated and flag cleared.'));
  }

  protected function assertSitemapLink($entity_type, $entity_id = NULL) {
    if (is_array($entity_type)) {
      $links = xmlsitemap_link_load_multiple($entity_type);
      $link = $links ? reset($links) : FALSE;
    }
    else {
      $link = xmlsitemap_link_load($entity_type, $entity_id);
    }
    $this->assertTrue(is_array($link), 'Link loaded.');
    return $link;
  }

  protected function assertNoSitemapLink($entity_type, $entity_id = NULL) {
    if (is_array($entity_type)) {
      $links = xmlsitemap_link_load_multiple($entity_type);
      $link = $links ? reset($links) : FALSE;
    }
    else {
      $link = xmlsitemap_link_load($entity_type, $entity_id);
    }
    $this->assertFalse($link, 'Link not loaded.');
    return $link;
  }

  protected function assertSitemapLinkVisible($entity_type, $entity_id) {
    $link = xmlsitemap_link_load($entity_type, $entity_id);
    $this->assertTrue($link && $link['access'] && $link['status'], t('Sitemap link @type @id is visible.', array('@type' => $entity_type, '@id' => $entity_id)));
  }

  protected function assertSitemapLinkNotVisible($entity_type, $entity_id) {
    $link = xmlsitemap_link_load($entity_type, $entity_id);
    $this->assertTrue($link && !($link['access'] && $link['status']), t('Sitemap link @type @id is not visible.', array('@type' => $entity_type, '@id' => $entity_id)));
  }

  protected function assertSitemapLinkValues($entity_type, $entity_id, array $conditions) {
    $link = xmlsitemap_link_load($entity_type, $entity_id);

    if (!$link) {
      return $this->fail(t('Could not load sitemap link for @type @id.', array('@type' => $entity_type, '@id' => $entity_id)));
    }

    foreach ($conditions as $key => $value) {
      if ($value === NULL || $link[$key] === NULL) {
        // For nullable fields, always check for identical values (===).
        $this->assertIdentical($link[$key], $value, t('Identical values for @type @id link field @key.', array('@type' => $entity_type, '@id' => $entity_id, '@key' => $key)));
      }
      else {
        // Otherwise check simple equality (==).
        $this->assertEqual($link[$key], $value, t('Equal values for @type @id link field @key.', array('@type' => $entity_type, '@id' => $entity_id, '@key' => $key)));
      }
    }
  }

  protected function assertNotSitemapLinkValues($entity_type, $entity_id, array $conditions) {
    $link = xmlsitemap_link_load($entity_type, $entity_id);

    if (!$link) {
      return $this->fail(t('Could not load sitemap link for @type @id.', array('@type' => $entity_type, '@id' => $entity_id)));
    }

    foreach ($conditions as $key => $value) {
      if ($value === NULL || $link[$key] === NULL) {
        // For nullable fields, always check for identical values (===).
        $this->assertNotIdentical($link[$key], $value, t('Not identical values for @type @id link field @key.', array('@type' => $entity_type, '@id' => $entity_id, '@key' => $key)));
      }
      else {
        // Otherwise check simple equality (==).
        $this->assertNotEqual($link[$key], $value, t('Not equal values for link @type @id field @key.', array('@type' => $entity_type, '@id' => $entity_id, '@key' => $key)));
      }
    }
  }

  protected function assertRawSitemapLinks() {
    $links = func_get_args();
    foreach ($links as $link) {
      $path = url($link['loc'], array('language' => xmlsitemap_language_load($link['language']), 'absolute' => TRUE));
      $this->assertRaw($link['loc'], t('Link %path found in the sitemap.', array('%path' => $path)));
    }
  }

  protected function assertNoRawSitemapLinks() {
    $links = func_get_args();
    foreach ($links as $link) {
      $path = url($link['loc'], array('language' => xmlsitemap_language_load($link['language']), 'absolute' => TRUE));
      $this->assertNoRaw($link['loc'], t('Link %path not found in the sitemap.', array('%path' => $path)));
    }
  }

  protected function addSitemapLink(array $link = array()) {
    $last_id = &drupal_static(__FUNCTION__, 1);

    $link += array(
      'type' => 'testing',
      'id' => $last_id,
      'access' => 1,
      'status' => 1,
    );

    // Make the default path easier to read than a random string.
    $link += array('loc' => $link['type'] . '-' . $link['id']);

    $last_id = max($last_id, $link['id']) + 1;
    xmlsitemap_link_save($link);
    return $link;
  }

  protected function assertFlag($variable, $assert_value = TRUE, $reset_if_true = TRUE) {
    $value = xmlsitemap_var($variable);

    if ($reset_if_true && $value) {
      \Drupal::config('xmlsitemap.settings')->set($variable, FALSE);
    }

    return $this->assertEqual($value, $assert_value, "xmlsitemap_$variable is " . ($assert_value ? 'TRUE' : 'FALSE'));
  }

  protected function assertXMLSitemapProblems($problem_text = FALSE) {
    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertText(t('One or more problems were detected with your XML sitemap configuration'));
    if ($problem_text) {
      $this->assertText($problem_text);
    }
  }

  protected function assertNoXMLSitemapProblems() {
    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertNoText(t('One or more problems were detected with your XML sitemap configuration'));
  }

  /**
   * Fetch all seen watchdog messages.
   *
   * @todo Add unit tests for this function.
   */
  protected function getWatchdogMessages(array $conditions = array(), $reset = FALSE) {
    static $seen_ids = array();

    if (!module_exists('dblog') || $reset) {
      $seen_ids = array();
      return array();
    }

    $query = db_select('watchdog');
    $query->fields('watchdog', array('wid', 'type', 'severity', 'message', 'variables', 'timestamp'));
    foreach ($conditions as $field => $value) {
      if ($field == 'variables' && !is_string($value)) {
        $value = serialize($value);
      }
      $query->condition($field, $value);
    }
    if ($seen_ids) {
      $query->condition('wid', $seen_ids, 'NOT IN');
    }
    $query->orderBy('timestamp');
    $messages = $query->execute()->fetchAllAssoc('wid');

    $seen_ids = array_merge($seen_ids, array_keys($messages));
    return $messages;
  }

  protected function assertWatchdogMessage(array $conditions, $message = 'Watchdog message found.') {
    $this->assertTrue($this->getWatchdogMessages($conditions), $message);
  }

  protected function assertNoWatchdogMessage(array $conditions, $message = 'Watchdog message not found.') {
    $this->assertFalse($this->getWatchdogMessages($conditions), $message);
  }

  /**
   * Check that there were no watchdog errors or worse.
   */
  protected function assertNoWatchdogErrors() {
    $messages = $this->getWatchdogMessages();
    $verbose = array();

    foreach ($messages as $message) {
      $message->text = $this->formatWatchdogMessage($message);
      if (in_array($message->severity, array(WATCHDOG_EMERGENCY, WATCHDOG_ALERT, WATCHDOG_CRITICAL, WATCHDOG_ERROR, WATCHDOG_WARNING))) {
        $this->fail($message->text);
      }
      $verbose[] = $message->text;
    }

    if ($verbose) {
      array_unshift($verbose, '<h2>Watchdog messages</h2>');
      $this->verbose(implode("<br />", $verbose), 'Watchdog messages from test run');
    }

    // Clear the seen watchdog messages since we've failed on any errors.
    $this->getWatchdogMessages(array(), TRUE);
  }

  /**
   * Format a watchdog message in a one-line summary.
   *
   * @param $message
   *   A watchdog messsage object.
   * @return
   *   A string containing the watchdog message's timestamp, severity, type,
   *   and actual message.
   */
  private function formatWatchdogMessage($message) {
    static $levels;

    if (!isset($levels)) {
      module_load_include('admin.inc', 'dblog');
      $levels = watchdog_severity_levels();
    }

    return t('@timestamp - @severity - @type - !message', array(
      '@timestamp' => $message->timestamp,
      '@severity' => $levels[$message->severity],
      '@type' => $message->type,
      //'!message' => theme_dblog_message(array('event' => $message, 'link' => FALSE)),
    ));
  }

  /**
   * Log verbose message in a text file.
   *
   * This is a copy of DrupalWebTestCase->verbose() but allows a customizable
   * summary message rather than hard-coding 'Verbose message'.
   *
   * @param $verbose_message
   *   The verbose message to be stored.
   * @param $message
   *   Message to display.
   * @see simpletest_verbose()
   *
   * @todo Remove when http://drupal.org/node/800426 is fixed.
   */
  protected function verbose($verbose_message, $message = 'Verbose message') {
    if ($id = parent::verbose($verbose_message)) {
      $url = file_create_url($this->originalFileDirectory . '/simpletest/verbose/' . get_class($this) . '-' . $id . '.html');
      $this->error(l($message, $url, array('attributes' => array('target' => '_blank'))), 'User notice');
    }
  }

}
