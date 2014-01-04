<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Tests for AccountContact class
 */
class api_v3_AccountContactTest extends CiviUnitTestCase {
  protected $_individualID;
  function setUp() {
    // If your test manipulates any SQL tables, then you should truncate
    // them to ensure a consisting starting point for all tests
    // $this->quickCleanup(array('example_table_name'));
    parent::setUp();
    $this->_individualID = $this->individualCreate();
  }
  function tearDown() {
    $this->quickCleanup(array (
      'civicrm_account_contact'
    ));
    parent::tearDown();
  }

  /**
   * Test that a contact is created
   */
  function testCreate() {
    $createParams = array (
      'contact_id' => $this->_individualID,
      'accounts_display_name' => 'ballerina'
    );
    $result = $this->callAPIAndDocument('AccountContact', 'create', $createParams, __FUNCTION__, __FILE__);
    $this->getAndCheck($createParams, $result['id'], 'account_contact');
  }
  /**
   * Test that a contact is created
   */
  function testGetValue() {
    $createParams = array (
      'contact_id' => $this->_individualID,
      'accounts_display_name' => 'ballerina',
      'accounts_contact_id' => 'ac62bce6-47a3-4937-9299-69c39693993a',
      'plugin' => 'xero'
    );
    $result = $this->callAPISuccess('AccountContact', 'create', $createParams);
    $getParams = array (
      'accounts_contact_id' => 'ac62bce6-47a3-4937-9299-69c39693993a',
      'plugin' => 'xero',
      'return' => 'id'
    );
    $result = $this->callAPIAndDocument('AccountContact', 'getvalue', $getParams, __FUNCTION__, __FILE__);
  }
  /**
   * Test saving & retrieval of Xero Date
   */
  function testGetDate() {
    $xeroDate = '2014-01-01T22:05:42.557';
    $createParams = array (
      'contact_id' => $this->_individualID,
      'accounts_display_name' => 'ballerina',
      'accounts_contact_id' => 'ac62bce6-47a3-4937-9299-69c39693993a',
      'plugin' => 'xero',
      'accounts_modified_date' => $xeroDate
    );
    $this->callAPISuccess('AccountContact', 'create', $createParams);
    $result = $this->callAPISuccess('AccountContact', 'getsingle', array ());
    $this->assertEquals($result['accounts_modified_date'], '2014-01-01 22:05:42');
  }

  /**
   * Test that getfields returns what it would if core getfields worked
   */
  function testGetFields() {
    $result = $this->callAPIAndDocument('AccountContact', 'getfields', array (), __FUNCTION__, __FILE__);
    $expected = array (
      'id' => array (
        'name' => 'id',
        'type' => 1,
        'required' => 1,
        'api.aliases' => array (
          '0' => 'account_contact_id'
        )
      ),

      'contact_id' => array (
        'name' => 'contact_id',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact'
      ),

      'accounts_contact_id' => array (
        'name' => 'accounts_contact_id',
        'type' => 2,
        'maxlength' => 128,
        'size' => 20
      ),

      'last_sync_date' => array (
        'name' => 'last_sync_date',
        'type' => 256,
        'title' => 'Last Sync Date',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
      ),

      'accounts_modified_date' => array (
        'name' => 'accounts_modified_date',
        'type' => 4,
        'title' => 'Accounts Modified Date'
      ),
      'accounts_display_name' => array (
        'name' => 'accounts_display_name',
        'type' => 2,
        'title' => 'Display Name',
        'maxlength' => 128,
        'size' => 45
      ),

      'accounts_data' => array (
        'name' => 'accounts_data',
        'type' => 2,
        'title' => 'Account System Data',
        'maxlength' => 128,
        'size' => 45
      ),

      'plugin' => array (
        'name' => 'plugin',
        'type' => 2,
        'title' => 'Account Plugin',
        'maxlength' => 32,
        'size' => 20
      )
    );
    $this->assertEquals($expected, $result['values']);
  }
}