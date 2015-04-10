<?php

require_once 'accountsync.civix.php';

/**
 * Implements hook_civicrm_config().
 */
function accountsync_civicrm_config(&$config) {
  _accountsync_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 */
function accountsync_civicrm_xmlMenu(&$files) {
  _accountsync_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 */
function accountsync_civicrm_install() {
  return _accountsync_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 */
function accountsync_civicrm_uninstall() {
  return _accountsync_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 */
function accountsync_civicrm_enable() {
  return _accountsync_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 */
function accountsync_civicrm_disable() {
  return _accountsync_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param string $op The type of operation being performed; 'check' or 'enqueue'
 * @param CRM_Queue_Queue $queue for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function accountsync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _accountsync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function accountsync_civicrm_managed(&$entities) {
  return _accountsync_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_config().
 */
function accountsync_civicrm_alterSettingsFolders(&$metaDataFolders) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'settings';
  if (!in_array($extDir, $metaDataFolders)) {
    $metaDataFolders[] = $extDir;
  }
}

/**
 * Implements hook_civicrm_post().
 *
 * If an entity is created or updated check if it is an entity which
 * we want to trigger a contact or invoice to be pushed to the account system or to trigger an update in the accounts system
 * we write that to the accounts contact or accounts invoice table at this point.
 *
 * @param string $op
 * @param string $objectName
 * @param int $objectId
 * @param object $objectRef
 */
function accountsync_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  $whitelistOps = array('update', 'create', 'restore', 'edit');

  if (!in_array($op, $whitelistOps)) {
    return;
  }

  $entities = civicrm_api3('setting', 'get', array('group' => 'Account Sync'));
  $createEntities = CRM_Utils_Array::value('account_sync_queue_contacts', $entities['values'][CRM_Core_Config::domainID()], array());
  $updateEntities = CRM_Utils_Array::value('account_sync_queue_update_contacts', $entities['values'][CRM_Core_Config::domainID()], array());
  $invoiceEntities = CRM_Utils_Array::value('account_sync_queue_create_invoice', $entities['values'][CRM_Core_Config::domainID()], array());
  $objectName = _accountsync_map_object_name_to_entity($objectName);

  if (in_array($objectName, array_merge($createEntities, $updateEntities))) {
    if (isset($objectRef->contact_id)) {
      $contactID = $objectRef->contact_id;
    }
    else {
      $contactID = $objectRef->id;
    }
    _accountsync_create_account_contact($contactID, in_array($objectName, $createEntities));
  }

  if (in_array($objectName, $invoiceEntities)) {
    // we won't do updates as the invoices get 'locked' in the accounts system
    _accountsync_create_account_invoice($objectRef->id, TRUE);
  }
}

/**
 * Implements hook_civicrm_pre().
 *
 * @param string $op
 * @param string $objectName
 * @param int $id
 * @param array $params
 */
function accountsync_civicrm_pre($op, $objectName, $id, &$params) {
  $objectName = _accountsync_map_object_name_to_entity($objectName);
  _accountsync_handle_contact_deletion($op, $objectName, $id);
  _accountsync_handle_contribution_deletion($op, $objectName, $id);

}

/**
 * Update account_contact record to reflect attempt to delete contact.
 *
 * @param string $op
 * @param string $entity
 * @param int $id
 */
function _accountsync_handle_contact_deletion($op, $entity, $id) {
  if (($op == 'delete'|| $op == 'trash') && ($entity == 'Contact')) {
    try {
      $accountContact = civicrm_api3('account_contact', 'getsingle', array(
        'contact_id' => $id,
        'plugin' => 'xero')
      );

      if (empty($accountContact['accounts_contact_id'])) {
        civicrm_api3('account_contact', 'delete', array('id' => $accountContact['id']));
      }
      elseif ($op == 'trash') {
        CRM_Core_Session::setStatus(ts('You are deleting a contact that has been synced to your accounts system. It is recommended you restore the contact & fix this'));
      }
      else {
        civicrm_api3('account_contact', 'delete', array('id' => $accountContact['id']));
        CRM_Core_Session::setStatus(ts('You have deleted a contact that has been synced to your accounts system. The sync tracking record has been deleted. Resolution is unclear'));

      }
    }
    catch(Exception $e) {
      //doesn't exist - move along, nothing to see here
    }
  }
}

/**
 * Update account_contact record to relect attempt to delete contact.
 *
 * @param string $op
 * @param string $objectName
 * @param int $id
 */
function _accountsync_handle_contribution_deletion($op, $objectName, $id) {
  if (($op == 'delete') && ($objectName == 'Contribution')) {
    try {
      $accountInvoice = civicrm_api3('account_invoice', 'getsingle', array(
        'contribution_id' => $id,
        'plugin' => 'xero')
      );
      if (empty($accountInvoice['accounts_invoice_id'])) {
        civicrm_api3('account_invoice', 'delete', array('id' => $accountInvoice['id']));
      }
      else {
        //here we need to create a way to void
        CRM_Core_Session::setStatus(ts('You have deleted an invoice that has been synced to your accounts system. You will need to remove it from your accounting package'));
      }
    }
    catch (Exception $e) {
      //doesn't exist - move along, nothing to see here
    }
  }
}

/**
 * Get Entity name from object name.
 *
 * This mostly exists because contact has several subtypes.
 *
 * @param string $objectName
 *
 * @return string
 *   Entity name
 */
function _accountsync_map_object_name_to_entity($objectName) {
  $contactEntities = array('Contact', 'Individual', 'Organization', 'Household');
  if (in_array($objectName, $contactEntities)) {
    return 'Contact';
  }
  return $objectName;
}
/**
 * Get array of enabled plugins.
 *
 * Currently we don't have a mechanism for this & are just returning xero.
 */
function _accountsync_get_enabled_plugins() {
  return array('xero');
}

/**
 * Create account contact record or set needs_update flag.
 *
 * In this function we check whether the contact exists. If it does we set the
 * update flag.
 *
 * If it doesn't then depending on the $createNew variable we will create a new
 * contact.
 *
 * @param int $contactID
 * @param bool $createNew
 *   Should a new contact be created if one does not exist?
 */
function _accountsync_create_account_contact($contactID, $createNew) {
  $accountContact = array('contact_id' => $contactID);
  foreach (_accountsync_get_enabled_plugins() as $plugin) {
    $accountContact['plugin'] = $plugin;
    try {
      $accountContact['id'] = civicrm_api3('account_contact', 'getvalue', array_merge($accountContact, array('return' => 'id')));
      $accountContact['accounts_needs_update'] = 1;
      civicrm_api3('account_contact', 'create', $accountContact);
    }
    catch (CiviCRM_API3_Exception $e) {
      // new contact
      if (!$createNew) {
        continue;
      }
      try {
        $accountContact['accounts_needs_update'] = 1;
        civicrm_api3('account_contact', 'create', $accountContact);
      }
      catch (CiviCRM_API3_Exception $e) {
        // unknown failure
      }
    }
  }
}

/**
 * Create account invoice record or set needs_update flag.
 *
 * @param int $contributionID
 * @param bool $createNew
 */
function _accountsync_create_account_invoice($contributionID, $createNew) {
  $accountInvoice = array('contribution_id' => $contributionID, 'accounts_needs_update' => 1);
  try {
    $accountInvoice['id'] = civicrm_api3('account_invoice', 'getvalue', array(
      'plugin' => 'xero',
      'return' => 'id',
      'contribution_id' => $contributionID,
      ));
  }
  catch (CiviCRM_API3_Exception $e) {
    // new contact
    if (!$createNew) {
      return;
    }
  }
  $accountInvoice['plugin'] = 'xero';
  try {
    civicrm_api3('account_invoice', 'create', $accountInvoice);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Unknown failure.
  }
}

/**
 * Implements hook_civicrm_merge().
 *
 * If the 'deleted' contact has a accounting system record synced to it and the retained one does not then the old one will be
 * removed and the xero id will be assigned to the retained one
 *
 * @param string $type
 * @param array $data
 * @param null $new_id
 * @param null $old_id
 * @param null $tables
 */
function accountsync_civicrm_merge($type, $data, $new_id = NULL, $old_id = NULL, $tables = NULL) {
  if (!empty($new_id) && !empty($old_id) && $type == 'sqls') {
    try {
      //@todo - this will only move old contact ref to the new one - if both have xero accounts
      // then it will fail
      $accountContact = civicrm_api3('account_contact', 'getsingle', array('plugin' => 'xero', 'contact_id' => $old_id));
      civicrm_api3('account_contact', 'delete', array('contact_id' => $old_id));
      $accountContact['contact_id'] = $new_id;
      civicrm_api3('account_contact', 'create', $accountContact);
    }
    catch (Exception $e) {
      //nothing to do here
    }
  }
}
