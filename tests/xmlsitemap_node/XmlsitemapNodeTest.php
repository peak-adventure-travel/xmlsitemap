<?php
/**
 * @file
 * Unit tests for the xmlsitemap_node module.
 */

namespace Tests\xmlsitemap_node;

include './xmlsitemap_node/xmlsitemap_node.module';
use PHPUnit\Framework\TestCase;

class XmlsitemapNodeTest extends TestCase
{

  /**
   * Tests.
   */
  public function testValidateRobotsNoIndexValidatesCorrectly()
  {
    $node = new \stdClass();
    $node->language = 'en';
    $node->metatags = ['en' => ['robots' => ['value' => ['noindex' => 'noindex']]]];

    $result = validateRobotsNoIndex($node);
    $this->assertTrue($result, 'Returns true if robots has noindex value');

    $node->metatags = ['en' => ['robots' => ['value' => ['noindex' => '']]]];

    $result = validateRobotsNoIndex($node);
    $this->assertFalse($result, 'Returns false if noindex has empty value');

    $node->metatags = [];

    $result = validateRobotsNoIndex($node);
    $this->assertFalse($result, 'Returns false if metatags does not have robots value');

    $node = new \stdClass();
    $node->language = 'en';

    $result = validateRobotsNoIndex($node);
    $this->assertFalse($result, 'Returns false if there is no metatags property');
  }
}
