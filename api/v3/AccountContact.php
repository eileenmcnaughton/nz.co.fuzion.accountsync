<?php

/**
 * AccountContact.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_account_contact_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * AccountContact.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_create($params) {
  return _civicrm_api3_basic_create('CRM_Accountsync_BAO_AccountContact', $params);
}

/**
 * AccountContact.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Accountsync_BAO_AccountContact', $params);
}

/**
 * AccountContact.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_get($params) {
  return _civicrm_api3_basic_get('CRM_Accountsync_BAO_AccountContact', $params);
}

/**
 * AccountContact.getfields API
 * (we can't rely on generic as it won't look at our DAO)
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_getfields($params) {
  return civicrm_api3_create_success(
    array (
      'id' => array (
        'name' => 'id',
        'type' => 1,
        'required' => 1,
        'api.aliases' => array (
          '0' => 'account_contact_id'
        )
      ),

      'contact_id' => array (
        'name' => 'contact_id',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact'
      ),

      'accounts_contact_id' => array (
        'name' => 'accounts_contact_id',
        'type' => 2,
        'maxlength' => 128,
        'size' => 20
      ),

      'last_sync_date' => array (
        'name' => 'last_sync_date',
        'type' => 256,
        'title' => 'Last Sync Date',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
      ),

      'accounts_modified_date' => array (
        'name' => 'accounts_modified_date',
        'type' => 4,
        'title' => 'Accounts Modified Date'
      ),
      'accounts_display_name' => array (
        'name' => 'accounts_display_name',
        'type' => 2,
        'title' => 'Display Name',
        'maxlength' => 128,
        'size' => 45
      ),

      'accounts_data' => array (
        'name' => 'accounts_data',
        'type' => 32,
        'title' => 'Account System Data',
        'size' => 45
      ),
      'error_data' => array (
        'name' => 'error_data',
        'type' => 32,
        'title' => 'Error Data',
        'size' => 45
      ),
      'accounts_needs_update' => array (
        'name' => 'accounts_needs_update',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => 'Update Accounts?',
      ),
      'plugin' => array (
        'name' => 'plugin',
        'type' => 2,
        'title' => 'Account Plugin',
        'maxlength' => 32,
        'size' => 20,
        'api.required' => TRUE,
      )
    ));
}
