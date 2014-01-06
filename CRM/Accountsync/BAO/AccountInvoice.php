<?php

class CRM_Accountsync_BAO_AccountInvoice extends CRM_Accountsync_DAO_AccountInvoice {

  /**
   * Create a new AccountInvoice based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Accountsync_DAO_AccountInvoice|NULL
   */
  public static function create($params) {
    $className = 'CRM_Accountsync_DAO_AccountInvoice';
    $entityName = 'AccountInvoice';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }
}
