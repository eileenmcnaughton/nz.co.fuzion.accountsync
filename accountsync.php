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
  $createEntities = CRM_Utils_Array::value('account_sync_queue_contacts', $entities['values'][CRM_Core_Config::domainID()], array());
  $updateEntities = CRM_Utils_Array::value('account_sync_queue_update_contacts',$entities['values'][CRM_Core_Config::domainID()], array());
  $invoiceEntities = CRM_Utils_Array::value('account_sync_queue_create_invoice', $entities['values'][CRM_Core_Config::domainID()], array());

  if(in_array($objectName, array_merge($createEntities, $updateEntities))) {
    _accountsync_create_account_contact($objectRef->contact_id, in_array($objectName, $createEntities));
  }

  if(in_array($objectName, $invoiceEntities)) {
    // we won't do updates as the invoices get 'locked' in the accounts system
    _accountsync_create_account_invoice($objectRef->id, TRUE);
  }
}

/**
 * Create account contact record or set needs_update flag
 * @param integer $contactID
 */
function _accountsync_create_account_contact($contactID, $createNew) {
  $accountContact = array('contact_id' => $contactID, 'accounts_needs_update' => 1);
  try {
    $accountContact['id'] = civicrm_api3('account_contact', 'getvalue', array('plugin' => 'xero', 'return' => 'id', 'contact_id' => $contactID));
  }
  catch (CiviCRM_API3_Exception $e) {
    // new contact
    if(!$createNew) {
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
  /**
   * Create account invoice record or set needs_update flag
   * @param integer $contributionID
   */
  function _accountsync_create_account_invoice($contributionID, $createNew) {
    $accountInvoice = array('contribution_id' => $contributionID, 'accounts_needs_update' => 1);
    try {
      $accountInvoice['id'] = civicrm_api3('account_invoice', 'getvalue', array('plugin' => 'xero', 'return' => 'id', 'contribution_id' => $contributionID));
    }
    catch (CiviCRM_API3_Exception $e) {
      // new contact
      if(!$createNew) {
        return;
      }
    }
    $accountInvoice['plugin'] = 'xero';
    try {
      civicrm_api3('account_invoice', 'create', $accountInvoice);
    }
    catch (CiviCRM_API3_Exception $e) {
      // unknown failure
    }
  }

  /**
   * Sample code from webform_civicrm.module 7.x-1.4
   * Implements hook_civicrm_merge().
   * Update submission data to reflect new cid when contacts are merged.
   */
  function accountsync_civicrm_merge($type, $data, $new_id = NULL, $old_id = NULL, $tables = NULL) {
    if (!empty($new_id) && !empty($old_id) && $type == 'sqls') {
      try {
        //@todo - this will only move old contact ref to the new one - if both have xero accounts
        // then it will fail
        $accountContact = civicrm_api3('account_contact', 'getsingle', array('plugin' => 'xero', 'contact_id' => $old_id));
        $accountContact['contact_id'] = $new_id;
        $accountContact = civicrm_api3('account_contact', 'create', $accountContact);
      }
      catch (Exception $e) {
        //nothing to do here
      }
    }
  }