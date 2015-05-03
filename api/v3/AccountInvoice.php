<?php

/**
 * AccountInvoice.create API specification.
 *
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_account_invoice_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * AccountInvoice.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_invoice_create($params) {
  return _civicrm_api3_basic_create('CRM_Accountsync_BAO_AccountInvoice', $params);
}

/**
 * AccountInvoice.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_account_invoice_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Accountsync_BAO_AccountInvoice', $params);
}

/**
 * AccountInvoice.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_account_invoice_get($params) {
  return _civicrm_api3_basic_get('CRM_Accountsync_BAO_AccountInvoice', $params);
}

/**
 * AccountInvoice.get derived invoice.
 *
 * This function compiles what I think an invoice returned from the CiviCRM api should look like
 * - unfortunately it takes a bit of building!
 * NB I did not go down the invoice.get path because it seemed to be that in
 * future it might be used for something else
 *
 * We also have a problem doing getfields for this as the whole getfields _spec thing struggles a bit here
 * - the generic getfields needs to be over-ridden due to the DAO not being in core
 * & then the spec thing doesn't quite work *
 * @todo - write a getfields that works - but for now array('id' => $contributionID)
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_invoice_getderived($params) {
  return civicrm_api3_create_success(CRM_Accountsync_BAO_AccountInvoice::getDerived($params));
}

/**
 * AccountContact.getfields API.
 *
 * (we can't rely on generic as it won't look at our DAO)
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_account_invoice_getfields($params) {
  return civicrm_api3_create_success(
    array(
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'required' => 1,
        'api.aliases' => array(
          '0' => 'account_invoice_id'
        )
      ),

      'contribution_id' => array(
        'name' => 'contribution_id',
        'type' => 1,
        'FKClassName' => 'CRM_Contribute_DAO_Contribution'
      ),

      'accounts_invoice_id' => array(
        'name' => 'accounts_invoice_id',
        'type' => 2,
        'maxlength' => 128,
        'size' => 20
      ),

      'connector_id' => array (
        'name' => 'connector_id',
        'type' => 2,
        'maxlength' => 128,
        'size' => 20,
        'title' => 'ID of connector or 0 for site wide',
        'api.default' => 0,
      ),

      'last_sync_date' => array(
        'name' => 'last_sync_date',
        'type' => 256,
        'title' => 'Last Sync Date',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
      ),

      'accounts_modified_date' => array(
        'name' => 'accounts_modified_date',
        'type' => 4,
        'title' => 'Accounts Modified Date'
      ),
      'accounts_status_id' => array(
        'name' => 'accounts_status_id',
        'type' => 2,
        'title' => 'Accounts Status',
        'maxlength' => 32,
      ),
      'accounts_data' => array(
        'name' => 'accounts_data',
        'type' => 32,
        'title' => 'Account System Data',
        'size' => 45,
      ),
      'error_data' => array(
        'name' => 'error_data',
        'type' => 32,
        'title' => 'Error Data',
        'size' => 45,
      ),
      'accounts_needs_update' => array(
        'name' => 'accounts_needs_update',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => 'Update Accounts?',
      ),
      'plugin' => array(
        'name' => 'plugin',
        'type' => 2,
        'title' => 'Account Plugin',
        'maxlength' => 32,
        'size' => 20,
        'api.required' => TRUE,
      )
    ));
}
/**
 * AccountInvoice.create API specification (optional).
 *
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_account_invoice_update_contribution(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * AccountInvoice.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws \Exception
 */
function civicrm_api3_account_invoice_update_contribution($params) {
  if ($params['accounts_status_id'] == 1) {
    CRM_Accountsync_BAO_AccountInvoice::completeContributionFromAccountsStatus($params);
    return civicrm_api3_create_success();
  }
  if ($params['accounts_status_id'] == 3) {
    CRM_Accountsync_BAO_AccountInvoice::cancelContributionFromAccountsStatus($params);
    return civicrm_api3_create_success();
  }
  throw new Exception('Currently only complete is supported');
 // return _civicrm_api3_basic_create('CRM_Accountsync_BAO_AccountInvoice', $params);
}
