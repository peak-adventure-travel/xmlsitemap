<?php
// $Id$

/**
 * @file
 * Creates cache files using cron tasks.
 */

/**
 * The following path must be changed if the file is moved from the directory
 * currently containing it.
 */
include_once '../../../../../includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Allow execution to continue even if the request gets canceled.
@ignore_user_abort(TRUE);

// Try to increase the maximum execution time if it is too low.
if (ini_get('max_execution_time') < 240) {
  @set_time_limit(240);
}

// Fetch the cron semaphore
$semaphore = variable_get('xmlsitemap_cron_semaphore', FALSE);

if ($semaphore) {
  if (REQUEST_TIME - $semaphore > 3600) {
    // Either the task has been running for more than an hour or the semaphore
    // was not reset due to a database error.
    watchdog('xmlsitemap', 'The task to build the cache files has been running for more than an hour and is most likely stuck.', array(), WATCHDOG_ERROR);
    
   // Release cron semaphore
    variable_del('cron_semaphore');
  }
  else {
    // The task is still running normally.
    watchdog('xmlsitemap', 'Attempting to re-run the task to build the cache files while it is already running.', array(), WATCHDOG_WARNING);
  }
}
else {
  // Register shutdown callback
  register_shutdown_function('xmlsitemap_cron_cleanup');
  
  // Lock cron semaphore
  variable_set('xmlsitemap_cron_semaphore', REQUEST_TIME);
  
  // Update the information about the sitemap chunks.
  xmlsitemap_chunk_count(TRUE);
  
  // Build the cache files for the sitemap chunks.
  $chunks_info = variable_get('xmlsitemap_sitemap_chunks_info', array());
  $md5 = substr(md5($base_url), 0, 8);
  $parent_dir = variable_get('xmlsitemap_cache_directory', file_directory_path() .'/xmlsitemap');
  foreach($chunk_info as $module => $info) {
    // if first chunk is less than zero, the module is not enabled.
    if ($info['first chunk'] < 0 || $info['chunks'] == 0) {
      continue;
    }
    if ($info['needs update']) {
      for ($chunk = $info['first chunk']; $chunk <= $info['first chunk'] + $info['chunks'] - 1; $chunk++) {
        $count = variable_get('xmlsitemap_chunk_size', 1000);
        $delta = $chunk - $info['first chunk'];
        $from = $delta * $count;
        $filename = $parent_dir .'/sitemap-'. $md5 . $info['id'] . $delta . $language->language;
        @unlink($filename);
        if (!($fp = @fopen($filename, 'wb+'))) {
          fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
          fwrite($fp, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9>"'."\n");
          module_invoke($module, 'xmlsitemap_links', 0, $from, $count);
          fwrite($fp, '</urlset>');
          fclose($fp);
        }
      }
    }
     
    // Release cron semaphore
    variable_del('xmlsitemap_cron_semaphore');
  }
}
