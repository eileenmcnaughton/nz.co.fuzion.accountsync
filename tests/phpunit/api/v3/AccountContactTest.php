<?php

use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class AccountContactTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Test\EntityTrait;
  use Test\ContactTestTrait;
  use Test\Api3TestTrait;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test that a contact is created
   */
  public function testActions(): void {
    $createParams = [
      'contact_id' => $this->individualCreate(),
      'accounts_display_name' => 'ballerina',
    ];
    $result = $this->callAPISuccess('AccountContact', 'create', $createParams);
    $created = $this->callAPISuccessGetSingle('AccountContact', ['id' => $result['id']]);
    $this->assertEquals('ballerina', $created['accounts_display_name']);
    $result = $this->callAPISuccess('AccountContact', 'create', $createParams + [
      'id' => $result['id'],
      'last_sync_date' => '2024-01-01',
    ]);
    $created = $this->callAPISuccessGetSingle('AccountContact', ['id' => $result['id']]);
    $this->assertEquals('2024-01-01 00:00:00', $created['last_sync_date']);
    $this->callAPISuccess('AccountContact', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test that getfields returns what it would if core getfields worked
   */
  public function testGetFields(): void {
    $result = $this->callAPISuccess('AccountContact', 'getfields');
    $expected = [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'required' => TRUE,
        'api.aliases' => [
          '0' => 'account_contact_id',
        ],
        'title' => 'ID',
        'description' => 'Unique AccountContact ID',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.id',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],

      'contact_id' => [
        'name' => 'contact_id',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'title' => 'Contact ID',
        'description' => 'FK to Contact',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.contact_id',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'EntityRef',
          'label' => 'CiviCRM Contact ID',
          'size' => 6,
          'maxlength' => 14,
        ],
        'add' => '4.4',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],

      'accounts_contact_id' => [
        'name' => 'accounts_contact_id',
        'type' => 2,
        'maxlength' => 128,
        'size' => 45,
        'title' => 'Accounts Contact ID',
        'description' => 'External Reference',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.accounts_contact_id',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 128,
          'size' => 45,
        ],
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],

      'last_sync_date' => [
        'name' => 'last_sync_date',
        'type' => 256,
        'title' => 'Last Sync Date',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'description' => 'When was the contact last synced.',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.last_sync_date',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' =>  TRUE,
      ],

      'accounts_modified_date' => [
        'name' => 'accounts_modified_date',
        'type' => 256,
        'title' => 'Accounts Modified Date',
        'description' => 'When was the invoice last Altered in the accounts system.',
        'required' => FALSE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.accounts_modified_date',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],
      'accounts_display_name' => [
        'name' => 'accounts_display_name',
        'type' => 2,
        'title' => 'Display Name',
        'maxlength' => 128,
        'size' => 45,
        'description' => 'Name from Accounts Package',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.accounts_display_name',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 128,
          'size' => 45,
        ],
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],

      'accounts_data' => [
        'name' => 'accounts_data',
        'type' => 32,
        'title' => 'Account System Data',
        'description' => 'json array of data as returned from accounts system',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.accounts_data',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'rows' => 2,
          'cols' => 80,
        ],
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],

      'plugin' => [
        'name' => 'plugin',
        'type' => 2,
        'title' => 'Account Plugin',
        'maxlength' => 32,
        'size' => 20,
        'description' => 'Name of plugin creating the account',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.plugin',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],
      'error_data' => [
        'name' => 'error_data',
        'type' => 32,
        'title' => 'Account Error Data',
        'description' => 'json array of error data as returned from accounts system',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.error_data',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'rows' => 2,
          'cols' => 80,
        ],
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],
      'accounts_needs_update' => [
        'name' => 'accounts_needs_update',
        'type' => 16,
        'title' => 'Accounts Needs Update',
        'description' => 'Include in next push to accounts',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.accounts_needs_update',
        'default' => '1',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
        ],
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],
      'connector_id' => [
        'name' => 'connector_id',
        'type' => 1,
        'title' => 'Connector ID',
        'description' => 'ID of connector. Relevant to connect to more than one account of the same type',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.connector_id',
        'default' => '0',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'size' => 6,
          'maxlength' => 14,
        ],
        'readonly' => TRUE,
        'add' => '4.4',
        'is_core_field' => TRUE,
      ],
      'do_not_sync' => [
        'name' => 'do_not_sync',
        'type' => 16,
        'title' => 'Do Not Sync',
        'description' => 'Do not sync this contact',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.do_not_sync',
        'default' => '0',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
        ],
        'add' => '4.6',
        'is_core_field' => TRUE,
      ],
      'is_error_resolved' => [
        'name' => 'is_error_resolved',
        'type' => 16,
        'title' => 'Error Resolved',
        'description' => 'Filter out if resolved',
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_account_contact.is_error_resolved',
        'default' => '0',
        'table_name' => 'civicrm_account_contact',
        'entity' => 'AccountContact',
        'bao' => 'CRM_Accountsync_DAO_AccountContact',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
        ],
        'add' => '5.56',
        'is_core_field' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $result['values']);
  }

}
