<?php

require_once 'accountsync.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function accountsync_civicrm_config(&$config) {
  _accountsync_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function accountsync_civicrm_xmlMenu(&$files) {
  _accountsync_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function accountsync_civicrm_install() {
  return _accountsync_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function accountsync_civicrm_uninstall() {
  return _accountsync_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function accountsync_civicrm_enable() {
  return _accountsync_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function accountsync_civicrm_disable() {
  return _accountsync_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function accountsync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _accountsync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function accountsync_civicrm_managed(&$entities) {
  return _accountsync_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_config
 */
function accountsync_civicrm_alterSettingsFolders(&$metaDataFolders){
  static $configured = FALSE;
  if ($configured) return;
  $configured = TRUE;

  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'settings';
  if(!in_array($extDir, $metaDataFolders)){
    $metaDataFolders[] = $extDir;
  }
}

function accountsync_civicrm_post($op, $objectName, $objectId, &$objectRef){
  $entities = civicrm_api3('setting', 'get', array('group' => 'Account Sync'));
  $createEntities = $entities['values'][CRM_Core_Config::domainID()]['account_sync_queue_contacts'];
  $updateEntities = $entities['values'][CRM_Core_Config::domainID()]['account_sync_queue_update_contacts'];

  if(!in_array($objectName, array_merge($createEntities, $updateEntities))) {
    return;
  }
  $contactID = $objectRef->contact_id;
  $accountContact = array('contact_id' => $contactID, 'accounts_needs_update' => 1);

  try {
    $accountContact['id'] = civicrm_api3('account_contact', 'getvalue', array('plugin' => 'xero', 'return' => 'id', 'contact_id' => $contactID));
  }
  catch (CiviCRM_API3_Exception $e) {
    // new contact
    if(!in_array($objectName, $createEntities)) {
      return;
    }
  }
  $accountContact['plugin'] = 'xero';
  try {
    civicrm_api3('account_contact', 'create', $accountContact);
  }
  catch (CiviCRM_API3_Exception $e) {
    // unknown failure
  }

}