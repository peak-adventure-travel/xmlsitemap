<?php
// $Id$

/**
 * @file
 * Integration file with rules.module.
 */

/**
 * @addtogroup xmlsitemap
 * @{
 */

/*****************************************************************************
 * Drupal hooks.
 ****************************************************************************/

/**
* Implementation of hook_rules_action_info().
*/
function xmlsitemap_node_rules_action_info() {
  return array(
    'xmlsitemap_node_action_set_priority' => array(
      'label' => t('Set the content priority in the XML site map'),
      'arguments' => array(
        'node' => array('type' => 'node', 'label' => t('Content')),
        'priority' => array('type' => 'number', 'label' => t('Priority')),
      ),
      'module' => 'XML Sitemap',
    ),
  );
}

/*****************************************************************************
 * Public functions.
 ****************************************************************************/

/**
 * Action implementation: change the node priority in the XML site map.
 */
function xmlsitemap_node_action_set_priority($node, $priority) {
  if ($priority >= 0.0 && $priority <= 1.0) {
    
  }
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
