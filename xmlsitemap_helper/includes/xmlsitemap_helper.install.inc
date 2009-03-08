<?php
// $Id$

/**
 * @file
 * Support file for installation/update functions.
 */

/**
 * @addtogroup xmlsitemap
 * @{
 */

/*****************************************************************************
 * Public functions.
 ****************************************************************************/

function xmlsitemap_helper_add_database_batch_operation($file, $function) {
  $batch = array(
    'operations' => array(
      array($function, array())
    ),
    'title' => t('Processing'),
    'progress_message' => '',
    'error_message' => '',
    'file' => $file,
  );
  batch_set($batch);
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
