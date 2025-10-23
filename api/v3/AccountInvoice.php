<?php

/**
 * AccountInvoice.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 * @throws CRM_Core_Exception
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
 * @throws CRM_Core_Exception
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_account_invoice_get($params) {
  return _civicrm_api3_basic_get('CRM_Accountsync_BAO_AccountInvoice', $params);
}

function _civicrm_api3_account_invoice_getderived_spec(&$spec) {
  $spec['id'] = [
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['contribution_id'],
    'name' => 'contribution_id',
    'title' => 'Contribution ID',
  ];
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_account_invoice_getderived($params) {
  return civicrm_api3_create_success(CRM_Accountsync_BAO_AccountInvoice::getDerived($params));
}

function _civicrm_api3_account_invoice_update_contribution_spec(&$spec) {
  $spec['accounts_status_id'] = [
    'type' => CRM_Utils_Type::T_INT,
    'name' => 'accounts_status_id',
    'title' => 'Accounts Status ID',
    'description' => 'Status in accounts system (mapped to CiviCRM definition)',
    'pseudoconstant' => [
      'callback' => 'CRM_Accountsync_BAO_AccountInvoice::getAccountStatuses',
    ],
  ];
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
function civicrm_api3_account_invoice_update_contribution(array $params): array {
  $accountsStatus = CRM_Core_PseudoConstant::getName('CRM_Accountsync_BAO_AccountInvoice', 'accounts_status_id', $params['accounts_status_id'] ?? '');
  if ($accountsStatus === 'completed') {
    CRM_Accountsync_BAO_AccountInvoice::completeContributionFromAccountsStatus();
  }
  elseif ($accountsStatus === 'cancelled') {
    CRM_Accountsync_BAO_AccountInvoice::cancelContributionFromAccountsStatus();
  }
  else {
    throw new Exception('Currently only completed/cancelled is supported');
  }
  return civicrm_api3_create_success();
}
