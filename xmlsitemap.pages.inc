<?php

/**
 * @file
 * Page callbacks for the xmlsitemap module.
 *
 * @ingroup xmlsitemap
 */
use Drupal\xmlsitemap\XmlSitemapInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Get the sitemap chunk/page of the current request.
 */
function xmlsitemap_get_current_chunk(XmlSitemapInterface $sitemap) {
  // Check if we should be displaing the index.
  $query = \Drupal::request()->query;
  $query_page = $query->get('page');
  if (!isset($query_page) || !is_numeric($query_page)) {
    if ($sitemap->getChunks() > 1) {
      return 'index';
    }
    else {
      return 1;
    }
  }
  else {
    return (int) $query_page;
  }
}

/**
 * Output the contents of a file to the browser and check caching headers.
 */
function xmlsitemap_output_file($file, array $headers = array()) {
  if (!file_exists($file) || !is_readable($file)) {
    throw new NotFoundHttpException();
  }
  $mtime = filemtime($file);
  $last_modified = gmdate(DATE_RFC1123, $mtime);
  $etag = '"' . md5($last_modified) . '"';

  // See if the client has provided the required HTTP headers.
  $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;
  $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;
  if ($if_modified_since && $if_none_match && $if_none_match == $etag && $if_modified_since == $last_modified) {
    header('HTTP/1.1 304 Not Modified');
    // All 304 responses must send an etag if the 200 response for the same object contained an etag
    header('Etag: ' . $etag);
    exit;
  }

  $headers += array(
    'Content-type' => 'text/xml; charset=utf-8',
    //'Content-length' => filesize($file),
    'Last-modified' => $last_modified,
    'Etag' => $etag,
    'Expires' => gmdate(DATE_RFC1123, $mtime + \Drupal::config('xmlsitemap.settings')->get('minimum_lifetime')),
    'Cache-Control' => 'must-revalidate',
    'X-Robots-Tag' => 'noindex, follow',
  );

  // Transfer the file as output.
  xmlsitemap_file_transfer($file, $headers);
}

/**
 * Modified version of file_transfer() that invokes hook_exit()s afterwards.
 *
 * @see file_transfer()
 */
function xmlsitemap_file_transfer($uri, $headers) {
  if (ob_get_level()) {
    ob_end_clean();
  }

  foreach ($headers as $name => $value) {
    drupal_add_http_header($name, $value);
  }
  drupal_send_headers();

  // Attempt to increase time to transfer file.
  drupal_set_time_limit(240);
  $scheme = file_default_scheme();
  // Transfer file in 16 KB chunks to save memory usage.
  if ($scheme && file_stream_wrapper_valid_scheme($scheme) && $fd = fopen($uri, 'rb')) {
    while (!feof($fd)) {
      print fread($fd, 1024 * 16);
    }
    fclose($fd);
  }
  else {
    throw new NotFoundHttpException();
  }
  exit();
}
