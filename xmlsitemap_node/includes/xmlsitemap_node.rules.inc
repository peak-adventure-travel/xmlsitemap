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
    'xmlsitemap_node_action_remove' => array(
      'label' => t('Remove the content from the site map'),
      'arguments' => array(
        'node' => array('type' => 'node', 'label' => t('Content')),
      ),
      'module' => 'XML Sitemap',
    ),
    'xmlsitemap_node_action_reset_priority' => array(
      'label' => t('Set the site map content priority to the content type value'),
      'arguments' => array(
        'node' => array('type' => 'node', 'label' => t('Content')),
      ),
      'module' => 'XML Sitemap',
    ),
    'xmlsitemap_node_action_set_priority' => array(
      'label' => t('Set the site map content priority'),
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
 * Action implementation: remove the node from the site map.
 */
function xmlsitemap_node_action_remove($node) {
  xmlsitemap_node_set_priority($node, -1.0);
}

/**
 * Action implementation: set the site map priority to the content type value.
 */
function xmlsitemap_node_action_reset_priority($node) {
  xmlsitemap_node_set_priority($node, -2.0);
}

/**
 * Action implementation: change the site map node priority.
 */
function xmlsitemap_node_action_set_priority($node, $priority) {
  if ($priority >= 0.0 && $priority <= 1.0) {
    xmlsitemap_node_set_priority($node, $priority);
  }
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
