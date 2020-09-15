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
  _accountsync_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 */
function accountsync_civicrm_uninstall() {
  _accountsync_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 */
function accountsync_civicrm_enable() {
  _accountsync_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 */
function accountsync_civicrm_disable() {
  _accountsync_civix_civicrm_disable();
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
  _accountsync_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_config().
 */
function accountsync_civicrm_alterSettingsFolders(&$metaDataFolders) {
  _accountsync_civix_civicrm_alterSettingsFolders($metaDataFolders);
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
  $whitelistOps = ['update', 'create', 'restore', 'edit'];

  if (!in_array($op, $whitelistOps)) {
    return;
  }

  $connectors = _accountsync_get_connectors();
  $objectName = _accountsync_map_object_name_to_entity($objectName);

  foreach ($connectors as $connector_id) {
    $createEntities = _accountsync_get_contact_create_entities($connector_id);
    $updateEntities = _accountsync_get_contact_update_entities($connector_id);
    $invoiceEntities = _accountsync_get_invoice_create_entities($connector_id);
    $skipInvoiceEntities = _accountsync_get_skip_invoice_create_entities($connector_id);
    $invoiceDayZero = _accountsync_get_invoice_day_zero($connector_id);
    if ($objectName == 'LineItem') {
      // If only some financial types apply to this connector and the line
      // item does not have one of them then skip to the next connector.
      $financial_type_id = is_array($objectRef) ? $objectRef['financial_type_id'] : $objectRef->financial_type_id;
      if (empty($financial_type_id) || !_accountsync_validate_for_connector($connector_id, $financial_type_id)) {
        continue;
      }
    }

    if (in_array($objectName, array_merge($createEntities, $updateEntities))) {
      if (isset($objectRef->contact_id)) {
        $contactID = $objectRef->contact_id;
      }
      else {
        switch ($objectName) {
          case 'LineItem':
            // See https://issues.civicrm.org/jira/browse/CRM-16268.
            $contribution_id = (is_array($objectRef)) ? $objectRef['contribution_id'] : $objectRef->contribution_id;
            if (!$contribution_id) {
              // We are updating a line item in what is probably a trivial way - e.g updating a price set field label
              // skip out early
              // @todo refine when we return early in case of odd cases where we DO want to know.
              return;
            }
            $contactID = civicrm_api3('Contribution', 'getvalue', [
              'id' => $contribution_id,
              'return' => 'contact_id',
            ]);
            break;

          case 'Contribution':
            $contribution_id = $objectRef->id;
            $contactID = civicrm_api3('Contribution', 'getvalue', [
              'id' => $contribution_id,
              'return' => 'contact_id',
            ]);
            break;
          case 'Contact':
            $contactID = $objectRef->id;
            break;
        }
      }

      if (isset($contactID)) {
        _accountsync_create_account_contact($contactID, in_array($objectName, $createEntities), $connector_id);
      }
    }

    if (in_array($objectName, $invoiceEntities)) {
      $contribution_id = ($objectName == 'LineItem') ? (is_array($objectRef) ? $objectRef['contribution_id'] : $objectRef->contribution_id) : $objectRef->id;
      if (isBeforeDayZero($objectName, $objectRef, $contribution_id, $invoiceDayZero)) {
        return;
      }
      if (isset($objectRef->payment_processor) && in_array($objectRef->payment_processor, $skipInvoiceEntities)) {
        return;
      }
      $pushEnabledStatuses = Civi::settings()->get('account_sync_push_contribution_status');
      //Don't create account invoice for zero contribution.
      //Skip contribution with status not enabled in settings.
      $contriValues = [];
      $returnValues = ['contribution_status_id', 'total_amount', 'is_test'];
      foreach ($returnValues as $key => $val) {
        if (!empty($objectRef->$val)) {
          $contriValues[$val] = $objectRef->$val;
          unset($returnValues[$key]);
        }
      }
      //Get from api if not present in $objectRef.
      if (!empty($returnValues)) {
        $apiValues = civicrm_api3('Contribution', 'getsingle', [
          'id' => $contribution_id,
          'return' => $returnValues,
        ]);
        $contriValues = array_merge($contriValues, $apiValues);
      }
      if ($contriValues['is_test'] || empty(floatval($contriValues['total_amount'])) || !in_array($contriValues['contribution_status_id'], $pushEnabledStatuses)) {
        continue;
      }
      // we won't do updates as the invoices get 'locked' in the accounts system
      _accountsync_create_account_invoice($contribution_id, TRUE, $connector_id);
    }
  }

}

/**
 * Is the invoice before the day zero.
 *
 * We only sync contributions afer the day zero date.
 *
 * @param string $objectName
 * @param CRM_Contribute_BAO_Contribution| CRM_Financial_BAO_LineItem $objectRef
 * @param int $contribution_id
 * @param string $invoiceDayZero
 *
 * @return bool
 * @throws \CiviCRM_API3_Exception
 *
 */
function isBeforeDayZero($objectName, $objectRef, $contribution_id, $invoiceDayZero) {
  if (empty($invoiceDayZero)) {
    return FALSE;
  }
  $receive_date = ($objectName == 'Contribution') ? $objectRef->receive_date : NULL;
  if (!$receive_date) {
    $receive_date = civicrm_api3('Contribution', 'getvalue', [
      'id' => $contribution_id,
      'return' => 'receive_date',
    ]);
  }
  if (strtotime($receive_date) < strtotime($invoiceDayZero)) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Get connectors configured on this site.
 *
 * If you have the nz.co.fuzion.connectors extension enabled and connectors available
 * through it then these will be used. Otherwise the settings table is used.
 *
 * @return array
 *   Array of ids from civicrm_connectors or 0 for settings only.
 */
function _accountsync_get_connectors() {
  $connectors = [];
  if (empty($connectors)) {
    try {
      $result = civicrm_api3('connector_type', 'get', [
        'module' => 'accountsync',
        'function' => 'credentials',
        'api.connector.get' => 1,
      ]);

      if (!$result['count']) {
        throw new Exception('No connector types found. Fallback to settings');
      }
      foreach ($result['values'] as $id => $connector_type) {
        $connectorResults = $connector_type['api.connector.get'];
        if (!empty($connectorResults['count'])) {
          foreach ($connectorResults['values'] as $connectorResult) {
            $connectors[] = $connectorResult['id'];
          }
        }
      }
      if (empty($connectors)) {
        throw new Exception('No connectors found. Fallback to settings');
      }

    }
    catch (Exception $e) {
      $connectors = [0];
    }
  }
  return $connectors;
}

/**
 * Check if entity is valid for this connector.
 *
 * If we are dealing with a Contribution AND the connector has a contact_id
 * then we will check that the line items in the invoice related to this contact ID.
 *
 * @param int $connector_id
 * @param int $financial_type_id
 *
 * @return bool
 */
function _accountsync_validate_for_connector($connector_id, $financial_type_id) {
  if ($connector_id == 0) {
    return TRUE;
  }
  $accounts_contact_id = _accountsync_get_account_contact_id($connector_id);
  static $connector_financial_types = [];
  if (!in_array($financial_type_id, $connector_financial_types)) {
    $connector_financial_types[$financial_type_id] = CRM_Accountsync_BAO_AccountInvoice::getAccountsContact($financial_type_id);
  }
  if ($accounts_contact_id == CRM_Utils_Array::value($financial_type_id, $connector_financial_types)) {
    return TRUE;
  }
  else {
    return FALSE;
  }

}

/**
 * Get the entities whose change should trigger a contact creation in the accounts package.
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Entities that result in a contact being created when the are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_contact_create_entities($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  $createEntities = CRM_Utils_Array::value('account_sync_queue_contacts', $entities, []);
  return $createEntities;
}

/**
 * Get the entities whose change should trigger a contact update in the accounts package.
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Entities that result in a contact being created when the are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_contact_update_entities($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  $createEntities = CRM_Utils_Array::value('account_sync_queue_update_contacts', $entities, []);
  return $createEntities;
}

/**
 * Get the entities whose change should trigger an invoice creation in the accounts package.
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Entities that result in an invoice being created when the are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_invoice_create_entities($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  $createEntities = CRM_Utils_Array::value('account_sync_queue_create_invoice', $entities, []);
  return $createEntities;
}

/**
 * Get the entities whose change should skip the trigger for invoice creation
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Payment processor entities that result in an invoice *not* being created when they are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_skip_invoice_create_entities($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  $skipEntities = CRM_Utils_Array::value('account_sync_skip_inv_by_pymt_processor', $entities, []);
  if ($skipEntities === ['']) {
    // There is some minor weirdness around the settings format sometimes. Handle.
    return [];
  }
  return $skipEntities;
}

/**
 * Get the entities whose change should trigger an invoice creation in the accounts package.
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Entities that result in an invoice being created when the are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_invoice_day_zero($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  $createEntities = CRM_Utils_Array::value('account_sync_contribution_day_zero', $entities, []);
  return $createEntities;
}

/**
 * Get the account contact id for the connector, if relevant.
 *
 * @param int $connector_id
 *   Connector ID if nz.co.fuzion.connectors is installed, else 0.
 *
 * @return array
 *   Entities that result in a contact being created when the are edited or created.
 *
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_account_contact_id($connector_id) {
  $entities = _accountsync_get_entity_action_settings($connector_id);
  return CRM_Utils_Array::value('account_sync_account_contact_id', $entities, []);
}

/**
 * Get the settings for which actions trigger accounts updates.
 *
 * In theory we can store this on the connector but at this stage we are
 * just using the general settings on a per-connector basis.
 *
 * @param int $connector_id
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function _accountsync_get_entity_action_settings($connector_id) {
  static $entities = [];
  if (empty($entities[$connector_id])) {
    $result = civicrm_api3('setting', 'get', ['group' => 'Account Sync']);
    // There appears to be a bug in CiviCRM core whereby sometimes extension
    // setting metadata isn't cached. If we think that is the case we'll flush the caches
    // to fix it. This happens rarely & represents a serious functionality breakage
    // so performance trade off is OK
    if (!isset($result['values'][CRM_Core_Config::domainID()]['account_sync_queue_contacts'])
      && !isset($result['values'][CRM_Core_Config::domainID()]['account_sync_queue_contacts'])
    ) {
      civicrm_api3('system', 'flush', []);
      $result = civicrm_api3('setting', 'get', ['group' => 'Account Sync']);
    }

    $entities[$connector_id] = $result['values'][CRM_Core_Config::domainID()];
    if (!empty($connector_id)) {
      try {
        // If we have an account contact then we should refer to line items
        // rather than contributions to figure out whether each line item relates
        // to the connector in question.
        // We can't rely on the financial type of the contribution
        // and, indeed, we provide for the possibility line items may
        // be related to account for more than one contact.
        // If we start storing this setting on the connector we can avoid looking
        // for it here.
        $connector_account_id = civicrm_api3('connector', 'getvalue', [
          'id' => $connector_id,
          'return' => 'contact_id',
        ]);
        if (!empty($connector_account_id)) {
          foreach (['account_sync_queue_contacts', 'account_sync_queue_create_invoice'] as $key) {
            foreach ($entities[$connector_id][$key] as $index => $entity) {
              if ($entity == 'Contribution') {
                $entities[$connector_id][$key][$index] = 'LineItem';
              }
            }
          }
          $entities[$connector_id]['account_sync_account_contact_id'] = $connector_account_id;
        }
      }
      catch (Exception $e) {
        // No change/
      }
    }
  }
  return $entities[$connector_id];
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
  _accountsync_handle_contact_deletion($op, $objectName, $id, $params);
  _accountsync_handle_contribution_deletion($op, $objectName, $id, $params);
}

/**
 * Update account_contact record to reflect attempt to delete contact.
 *
 * @param string $op
 * @param string $entity
 * @param int $id
 * @param array $params
 */
function _accountsync_handle_contact_deletion($op, $entity, $id, &$params) {
  if (($op == 'delete' || $op == 'trash' || ($op == 'update' && !empty($params['is_deleted']))) && ($entity == 'Contact')) {
    foreach (_accountsync_get_enabled_plugins() as $plugin) {
      try {
        $accountContact = civicrm_api3('account_contact', 'getsingle', [
            'contact_id' => $id,
            'plugin' => $plugin,
          ]
        );

        if (empty($accountContact['accounts_contact_id'])) {
          civicrm_api3('account_contact', 'delete', ['id' => $accountContact['id']]);
        }
        elseif ($op == 'trash' || $op == 'update') {
          CRM_Core_Session::setStatus(ts('You are deleting a contact that has been synced to your accounts system. It is recommended you restore the contact & fix this'));
        }
        else {
          civicrm_api3('account_contact', 'delete', ['id' => $accountContact['id']]);
          CRM_Core_Session::setStatus(ts('You have deleted a contact that has been synced to your accounts system. The sync tracking record has been deleted. Resolution is unclear'));

        }
      }
      catch (Exception $e) {
        //doesn't exist - move along, nothing to see here
      }
    }
  }
}

/**
 * Update account_contact record to relect attempt to delete contact.
 *
 * @param string $op
 * @param string $objectName
 * @param int $id
 * @param array $params
 */
function _accountsync_handle_contribution_deletion($op, $objectName, $id, &$params) {
  if (($op == 'delete') && ($objectName == 'Contribution')) {
    foreach (_accountsync_get_enabled_plugins() as $plugin) {
      try {
        $accountInvoice = civicrm_api3('AccountInvoice', 'getsingle', [
            'contribution_id' => $id,
            'plugin' => $plugin,
          ]
        );
        if (empty($accountInvoice['accounts_invoice_id'])) {
          civicrm_api3('AccountInvoice', 'delete', ['id' => $accountInvoice['id']]);
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
  $contactEntities = ['Contact', 'Individual', 'Organization', 'Household'];
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
  static $plugins = [];

  if (empty($plugins)) {
    /* Use the CiviCRM hook system to get a list of plugins.
     * This is largely undocumented, so just following the pattern of built-in
     * hooks.
     */
    CRM_Utils_Hook::singleton()
      ->invoke(1, $plugins, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_accountsync_plugins');
  }

  return $plugins;
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
 * @param int $connector_id
 *   ID of connector for civicrm_connector if nz.co.fuzion.connectors enabled.
 *   Otherwise this will be 0.
 */
function _accountsync_create_account_contact($contactID, $createNew, $connector_id) {
  $accountContact = [
    'contact_id' => $contactID,
    // Do not rollback on fail.
    'is_transactional' => FALSE,
  ];

  try {
    $contact = civicrm_api3("contact", "getsingle", [
      "id" => $contactID,
      "return" => ["id", "contact_is_deleted"],
    ]);
    if ($contact["contact_is_deleted"]) {
      // Contact is deleted, Skip the sync.
      return;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    // Contact not found, Skip the sync.
    return;
  }

  foreach (_accountsync_get_enabled_plugins() as $plugin) {
    $accountContact['plugin'] = $plugin;
    $accountContact['connector_id'] = $connector_id;
    try {
      $accountContact['id'] = civicrm_api3('account_contact', 'getvalue', array_merge($accountContact, ['return' => 'id']));
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
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function accountsync_civicrm_angularModules(&$angularModules) {
  _accountsync_civix_civicrm_angularModules($angularModules);
}

/**
 * Create account invoice record or set needs_update flag.
 *
 * @param int $contributionID
 * @param bool $createNew
 * @param int $connector_id
 *   ID of connector for civicrm_connector if nz.co.fuzion.connectors enabled.
 *   Otherwise this will be 0.
 */
function _accountsync_create_account_invoice($contributionID, $createNew, $connector_id) {
  $accountInvoice = [
    'contribution_id' => $contributionID,
    'accounts_needs_update' => 1,
    // Do not rollback on fail.
    'is_transactional' => FALSE,
  ];
  foreach (_accountsync_get_enabled_plugins() as $plugin) {
    unset($accountInvoice['id']); // Ensure id is not set in case of multiple plugins

    if ($connector_id) {
      $accountInvoice['connector_id'] = $connector_id;
    }
    try {
      $accountInvoice['id'] = civicrm_api3('AccountInvoice', 'getvalue', [
        'plugin' => $plugin,
        'return' => 'id',
        'contribution_id' => $contributionID,
        'connector_id' => $connector_id,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      // new contact
      if (!$createNew) {
        continue;
      }
    }
    $accountInvoice['plugin'] = $plugin;
    try {
      civicrm_api3('AccountInvoice', 'create', $accountInvoice);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Unknown failure.
    }
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
function accountsync_civicrm_merge($type, &$data, $new_id = NULL, $old_id = NULL, $tables = NULL) {
  if (!empty($new_id) && !empty($old_id) && $type == 'sqls') {
    /*
     * &$data will include an element that updates rows in the civicrm_activity_contact
     * table with $old_id to have $new_id. If a row already exists for $new_id then we
     * don't want to do this update
     */
    foreach (_accountsync_get_enabled_plugins() as $plugin) {
      try {
        $accountContact = civicrm_api3(
          'account_contact',
          'getsingle',
          [
            'plugin' => $plugin,
            'contact_id' => $new_id
          ]);
        if (!empty($accountContact)) {
          foreach ($data as $i => $sql) {
            if (strpos($sql, 'account_contact') !== false) {
              unset($data[$i]);
              break;
            }
          }
        }
      }
      catch (Exception $e) {
        //nothing to do here
      }
    }
  }
}

/**
 * Implements hook_civicrm_entityTypes.
 *
 * @param array $entityTypes
 *   Registered entity types.
 */
function accountsync_civicrm_entityTypes(&$entityTypes) {
  _accountsync_civix_civicrm_entityTypes($entityTypes);
}
