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
   * @return mixed
   *   Ignored value.
   */
  public static function accountPullPreSave($entity, &$data, &$save, &$params) {
    $codeVersion = explode('.', CRM_Utils_System::version());
    // if db.ver < code.ver, time to upgrade
    if (version_compare($codeVersion[0] . '.' . $codeVersion[1], 4.5) >= 0) {
      return CRM_Utils_Hook::singleton()->invoke(4, $entity,
        $data, $save, $params, CRM_Core_DAO::$_nullObject,
        CRM_Core_DAO::$_nullObject,
        'civicrm_accountPullPreSave'
      );
    }
    else {
      return CRM_Utils_Hook::singleton()->invoke(4, $entity,
        $data, $save, $params, CRM_Core_DAO::$_nullObject,
        'civicrm_accountPullPreSave'
      );
    }
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
   */
  public static function accountPushAlterMapped($entity, &$data, &$save, &$params) {
    $codeVersion = explode('.', CRM_Utils_System::version());
    if (version_compare($codeVersion[0] . '.' . $codeVersion[1], 4.5) >= 0) {
      return CRM_Utils_Hook::singleton()->invoke(4, $entity,
        $data, $save, $params, CRM_Core_DAO::$_nullObject,
        CRM_Core_DAO::$_nullObject,
        'civicrm_accountPushAlterMapped'
      );
    }
    else {
      return CRM_Utils_Hook::singleton()->invoke(4, $entity,
        $data, $save, $params, CRM_Core_DAO::$_nullObject,
        'civicrm_accountPushAlterMapped'
      );
    }
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
    $codeVersion = explode('.', CRM_Utils_System::version());
    if (version_compare($codeVersion[0] . '.' . $codeVersion[1], 4.5) >= 0) {
      return CRM_Utils_Hook::singleton()->invoke(3, $accountsData, $entity,
        $plugin, CRM_Core_DAO::$_nullObject, CRM_Core_DAO::$_nullObject,
        CRM_Core_DAO::$_nullObject,
        'civicrm_mapAccountsData'
      );
    }
    else {
      return CRM_Utils_Hook::singleton()->invoke(3, $accountsData, $entity,
        $plugin, CRM_Core_DAO::$_nullObject, CRM_Core_DAO::$_nullObject,
        'civicrm_mapAccountsData'
      );
    }
  }
}
