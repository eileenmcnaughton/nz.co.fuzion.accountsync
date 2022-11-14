<?php

/**
 * AccountContact.create API
 *
 * @param array $params
 *
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
 *
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
 *
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_get($params) {
  $accountContacts = _civicrm_api3_basic_get('CRM_Accountsync_BAO_AccountContact', $params);
  if (is_array($accountContacts['values'])) {
    // e.g when we are dealing with 'getcount we skip this.
    foreach ($accountContacts['values'] as $id => $accountContact) {
      if (!empty($accountContacts['values'][$id]['accounts_data'])) {
        $accountContacts['values'][$id]['accounts_data'] = json_decode($accountContacts['values'][$id]['accounts_data'], TRUE);
        CRM_Accountsync_Hook::mapAccountsData($accountContacts['values'][$id]['accounts_data'], 'contact', $params['plugin']);
      }
    }
  }
  return $accountContacts;
}

/**
 * AccountContact.get API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_account_contact_getsuggestions($params) {
  $contacts = civicrm_api3('AccountContact', 'get', array_merge($params, [
      'sequential' => 0,
      'accounts_display_name' => ['IS NOT NULL' => 1],
    ]
  ));
  $suggestions = $contacts['values'];
  foreach ($contacts['values'] as $id => $contact) {
    $possibleContacts = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('display_name', '=', $contact['accounts_display_name'])
      ->execute()
      ->indexBy('id');
    if ($possibleContacts->count()) {
      // Find and remove any possible contacts that are already synced to accounts
      $accountContacts = civicrm_api3('AccountContact', 'get',
        array_merge($params, [
            'contact_id' => ['IN' => $possibleContacts->column('id')],
          ]
        ))['values'];
      $possibleContacts = $possibleContacts->getArrayCopy();
      foreach ($accountContacts as $accountContact) {
        if (isset($possibleContacts[$accountContact['contact_id']])) {
          unset($possibleContacts[$accountContact['contact_id']]);
        }
      }

      // If there are multiple found contact select the first one
      $firstContactID = reset($possibleContacts)['id'];
      if (!empty($firstContactID)) {
        $suggestions[$id]['suggested_contact_id'] = $firstContactID;
        $suggestions[$id]['suggestion'] = 'link_contact';
      }
    }
    if (empty($suggestions[$id]['suggestion'])) {
      if (_civicrm_api3_account_contact_getsuggestions_looks_dodgey($contact)) {
        $suggestions[$id]['suggestion'] = 'do_not_sync';
      }
      else {
        $suggestions[$id]['suggestion'] = 'create_individual';
      }
    }
  }
  return civicrm_api3_create_success($suggestions, $params);
}

function _civicrm_api3_account_contact_savesuggestions_spec(&$spec) {
  $spec['suggestion_type']['title'] = 'Suggestion Type';
  $spec['suggestion_type']['description'] = 'The type of suggestion - currently only "link_contact" is supported';
  $spec['suggestion_type']['api.required'] = TRUE;
}

function civicrm_api3_account_contact_savesuggestions($params) {
  if ($params['suggestion_type'] !== 'link_contact') {
    throw new CRM_Core_Exception('Suggestion type must be "link_contact"');
  }

  $getSuggestionsParams = [];
  if (isset($params['options'])) {
    $getSuggestionsParams['options'] = $params['options'];
  }
  $suggestions = civicrm_api3('AccountContact', 'getsuggestions', $getSuggestionsParams)['values'];
  $updated = [];
  foreach ($suggestions as $suggestion) {
    if ($suggestion['suggestion'] !== 'link_contact') {
      continue;
    }

    $createParams = [
      'id' => $suggestion['id'],
      'contact_id' => $suggestion['suggested_contact_id'],
      'accounts_needs_update' => 1,
    ];
    civicrm_api3('AccountContact', 'create', $createParams);
    $updated[] = $suggestion['id'];
  }
  return civicrm_api3_create_success($updated, $params);
}

/**
 * Check if contact looks unlikely to be a real contact.
 *
 * In the future we'll build up a list of common patterns in this function.
 *
 * @param array $contact
 *
 * @return bool
 */
function _civicrm_api3_account_contact_getsuggestions_looks_dodgey($contact) {
  $firstCharacter = substr($contact['accounts_display_name'], 0, 1);
  if (is_numeric($firstCharacter)) {
    return TRUE;
  }
  return FALSE;
}
