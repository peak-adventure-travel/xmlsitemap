<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Controller\XmlSitemapController.
 */

namespace Drupal\xmlsitemap_engines_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Utility\UrlHelper;

class XmlSitemapEnginesTestController extends ControllerBase {

  public function render() {
    if (empty($_GET['sitemap']) || !UrlHelper::isValid($_GET['sitemap'])) {
      watchdog('xmlsitemap', 'No valid sitemap parameter provided.', array(), WATCHDOG_WARNING);
      // @todo Remove this? Causes an extra watchdog error to be handled.
      throw new NotFoundHttpException();
    }
    else {
      watchdog('xmlsitemap', 'Recieved ping for @sitemap.', array('@sitemap' => $_GET['sitemap']));
    }
    return new Response('', 200);
  }

}
