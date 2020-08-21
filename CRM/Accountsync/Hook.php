<?php

class CRM_Accountsync_Hook {

  /**
   * This hook allows an entity retrieved from Xero to be altered prior to processing.
   *
   * It is somewhat like the pre hook except that
   * - it allows the save to be cancelled
   * - it deals with a single Accounts entity not a CiviCRM entity
   *
   * @param string $entity entity - eg. 'contact'
   * @param array $data data from accounts being processing
   * @param bool $save
   *   Save? - set this to false if it should be skipped
   *
   * @param $params
   *
   * @return mixed
   *   Ignored value.
   */
  public static function accountPullPreSave($entity, &$data, &$save, &$params) {
    $null = NULL;
    return CRM_Utils_Hook::singleton()->invoke(['entity', 'data', 'save', 'params'], $entity,
      $data, $save, $params, $null,
      $null,
      'civicrm_accountPullPreSave'
    );
  }


  /**
   * This hook allows an entity retrieved from Xero to be altered prior to processing.
   *
   * It is somewhat like the pre hook except that
   * - it allows the save to be cancelled
   * - it deals with a single Accounts entity not a CiviCRM entity
   *
   * @param string $entity entity - eg. 'contact'
   * @param array $data data from accounts being processing
   * @param bool $save save? - set this to false if it should be skipped
   * @param $params
   *
   * @return mixed
   *   Ignore value.
   *
   */
  public static function accountPushAlterMapped($entity, &$data, &$save, &$params) {
    $null = NULL;
    return CRM_Utils_Hook::singleton()->invoke(['entity', 'data', 'save', 'params'], $entity,
      $data, $save, $params, $null,
      $null,
      'civicrm_accountPushAlterMapped'
    );
  }

  /**
   * This hook data transforms data stored in accounts_data to be formatted into a standard format.
   *
   * The called hook is expected to add a key 'civicrm_formatted' to the accountsData array
   * with the data using the same field names as the relevant CiviCRM api.
   *
   * @param array $accountsData data from accounts system
   * @param string $entity entity - eg. 'AccountContact'
   * @param string $plugin plugin in use
   *
   * @return mixed
   *   Ignore value.
   */
  public static function mapAccountsData(&$accountsData, $entity, $plugin) {
    $null = NULL;
    return CRM_Utils_Hook::singleton()->invoke(['accountsData', 'entity', 'plugin'], $accountsData, $entity,
      $plugin, $null, $null,
      $null,
      'civicrm_mapAccountsData'
    );
  }
}
