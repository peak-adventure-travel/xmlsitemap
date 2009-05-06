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
function xmlsitemap_user_rules_action_info() {
  return array(
    'xmlsitemap_user_action_remove' => array(
      'label' => t('Remove the user from the sitemap'),
      'arguments' => array(
        'user' => array('type' => 'user', 'label' => t('User')),
      ),
      'module' => 'XML sitemap',
    ),
    'xmlsitemap_user_action_set_priority' => array(
      'label' => t('Set the sitemap user priority'),
      'arguments' => array(
        'user' => array('type' => 'user', 'label' => t('User')),
        'priority' => array('type' => 'number', 'label' => t('Priority')),
      ),
      'module' => 'XML sitemap',
    ),
  );
}

/*****************************************************************************
 * Public functions.
 ****************************************************************************/

/**
 * Action implementation: remove the user from the sitemap.
 */
function xmlsitemap_user_action_remove($user) {
  xmlsitemap_user_set_priority($user, -1.0);
}

/**
 * Action implementation: change the sitemap user priority.
 */
function xmlsitemap_user_action_set_priority($user, $priority) {
  if ($priority >= 0.0 && $priority <= 1.0) {
    xmlsitemap_user_set_priority($user, $priority);
  }
}

/**
 * @} End of "addtogroup xmlsitemap".
 */
