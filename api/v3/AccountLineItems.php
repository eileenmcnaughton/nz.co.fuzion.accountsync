<?php

/**
 * AccountLineItems.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_account_line_items_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * AccountLineItems.create API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws CRM_Core_Exception
 */
function civicrm_api3_account_line_items_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * AccountLineItems.delete API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws CRM_Core_Exception
 */
function civicrm_api3_account_line_items_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * AccountLineItems.get API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws CRM_Core_Exception
 */
function civicrm_api3_account_line_items_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
