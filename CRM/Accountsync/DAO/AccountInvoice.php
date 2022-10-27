<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from nz.co.fuzion.accountsync/xml/schema/CRM/Accountsync/AccountInvoice.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:b5f5da92dc4ea18d65cd8943f6dc1867)
 */
use CRM_Accountsync_ExtensionUtil as E;

/**
 * Database access object for the AccountInvoice entity.
 */
class CRM_Accountsync_DAO_AccountInvoice extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '4.4';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_account_invoice';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique AccountInvoice ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * FK to contribution table.
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $contribution_id;

  /**
   * External Reference
   *
   * @var string|null
   *   (SQL type: varchar(128))
   *   Note that values will be retrieved from the database as a string.
   */
  public $accounts_invoice_id;

  /**
   * Status in accounts system (mapped to civicrm definition)
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $accounts_status_id;

  /**
   * When was the contact last synced.
   *
   * @var string
   *   (SQL type: timestamp)
   *   Note that values will be retrieved from the database as a string.
   */
  public $last_sync_date;

  /**
   * When was the invoice last Altered in the accounts system.
   *
   * @var string
   *   (SQL type: timestamp)
   *   Note that values will be retrieved from the database as a string.
   */
  public $accounts_modified_date;

  /**
   * json array of data as returned from accounts system
   *
   * @var string|null
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $accounts_data;

  /**
   * json array of error data as returned from accounts system
   *
   * @var string|null
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $error_data;

  /**
   * Include in next push to accounts
   *
   * @var bool|string|null
   *   (SQL type: tinyint)
   *   Note that values will be retrieved from the database as a string.
   */
  public $accounts_needs_update;

  /**
   * ID of connector. Relevant to connect to more than one account of the same type
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $connector_id;

  /**
   * Name of plugin creating the account
   *
   * @var string|null
   *   (SQL type: varchar(32))
   *   Note that values will be retrieved from the database as a string.
   */
  public $plugin;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_account_invoice';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Account Invoices') : E::ts('Account Invoice');
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_id', 'civicrm_contribution', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('Unique AccountInvoice ID'),
          'required' => TRUE,
          'where' => 'civicrm_account_invoice.id',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'readonly' => TRUE,
          'add' => '4.4',
        ],
        'contribution_id' => [
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Contribution ID'),
          'description' => E::ts('FK to contribution table.'),
          'where' => 'civicrm_account_invoice.contribution_id',
          'default' => NULL,
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
          'add' => '4.4',
        ],
        'accounts_invoice_id' => [
          'name' => 'accounts_invoice_id',
          'type' => CRM_Utils_Type::T_STRING,
          'description' => E::ts('External Reference'),
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'where' => 'civicrm_account_invoice.accounts_invoice_id',
          'default' => NULL,
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'accounts_status_id' => [
          'name' => 'accounts_status_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('Status in accounts system (mapped to civicrm definition)'),
          'where' => 'civicrm_account_invoice.accounts_status_id',
          'default' => '0',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'last_sync_date' => [
          'name' => 'last_sync_date',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => E::ts('Last Sync Date'),
          'description' => E::ts('When was the contact last synced.'),
          'required' => TRUE,
          'where' => 'civicrm_account_invoice.last_sync_date',
          'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'accounts_modified_date' => [
          'name' => 'accounts_modified_date',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => E::ts('Accounts Modified Date'),
          'description' => E::ts('When was the invoice last Altered in the accounts system.'),
          'required' => FALSE,
          'where' => 'civicrm_account_invoice.accounts_modified_date',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'accounts_data' => [
          'name' => 'accounts_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Account System Data'),
          'description' => E::ts('json array of data as returned from accounts system'),
          'where' => 'civicrm_account_invoice.accounts_data',
          'default' => NULL,
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'error_data' => [
          'name' => 'error_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Account Error Data'),
          'description' => E::ts('json array of error data as returned from accounts system'),
          'where' => 'civicrm_account_invoice.error_data',
          'default' => NULL,
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'accounts_needs_update' => [
          'name' => 'accounts_needs_update',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => E::ts('Accounts Needs Update'),
          'description' => E::ts('Include in next push to accounts'),
          'where' => 'civicrm_account_invoice.accounts_needs_update',
          'default' => '0',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'connector_id' => [
          'name' => 'connector_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('ID of connector. Relevant to connect to more than one account of the same type'),
          'where' => 'civicrm_account_invoice.connector_id',
          'default' => '0',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
        'plugin' => [
          'name' => 'plugin',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Account Plugin'),
          'description' => E::ts('Name of plugin creating the account'),
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'where' => 'civicrm_account_invoice.plugin',
          'table_name' => 'civicrm_account_invoice',
          'entity' => 'AccountInvoice',
          'bao' => 'CRM_Accountsync_DAO_AccountInvoice',
          'localizable' => 0,
          'add' => '4.4',
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'account_invoice', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'account_invoice', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [
      'index_contribution_invoice' => [
        'name' => 'index_contribution_invoice',
        'field' => [
          0 => 'contribution_id',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_account_invoice::0::contribution_id',
      ],
      'UI_account_system_id' => [
        'name' => 'UI_account_system_id',
        'field' => [
          0 => 'accounts_invoice_id',
          1 => 'connector_id',
          2 => 'plugin',
        ],
        'localizable' => FALSE,
        'unique' => TRUE,
        'sig' => 'civicrm_account_invoice::1::accounts_invoice_id::connector_id::plugin',
      ],
      'UI_invoice_id_plugin' => [
        'name' => 'UI_invoice_id_plugin',
        'field' => [
          0 => 'contribution_id',
          1 => 'connector_id',
          2 => 'plugin',
        ],
        'localizable' => FALSE,
        'unique' => TRUE,
        'sig' => 'civicrm_account_invoice::1::contribution_id::connector_id::plugin',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
