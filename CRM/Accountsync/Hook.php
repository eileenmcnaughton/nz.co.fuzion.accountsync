<?php

class CRM_Accountsync_Hook {

  /**
   * This hook allows an entity retrieved from Xero to be altered prior to processing
   * It is somewhat like the pre hook except that
   * - it allows the save to be cancelled
   * - it deals with a single Accounts entity not a CiviCRM entity
   *
   * @param string $entity entity - eg. 'contact'
   * @param array $data data from accounts being processing
   * @param boolean $save save? - set this to false if it should be skipped
   */
  static function accountPullPreSave($entity, &$data, &$save, &$params) {
    return CRM_Utils_Hook::singleton()->invoke(4, $entity,
      $data, $save, $params, CRM_Core_DAO::$_nullObject,
      'civicrm_accountPullPreSave'
    );
  }


  /**
   * This hook allows an entity retrieved from Xero to be altered prior to processing
   * It is somewhat like the pre hook except that
   * - it allows the save to be cancelled
   * - it deals with a single Accounts entity not a CiviCRM entity
   *
   * @param string $entity entity - eg. 'contact'
   * @param array $data data from accounts being processing
   * @param boolean $save save? - set this to false if it should be skipped
   * @param $params
   *
   * @return
   */
  static function accountPushAlterMapped($entity, &$data, &$save, &$params) {
    return CRM_Utils_Hook::singleton()->invoke(4, $entity,
      $data, $save, $params, CRM_Core_DAO::$_nullObject,
      'civicrm_accountPushAlterMapped'
    );
  }
}
