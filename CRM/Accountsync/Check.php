<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\AccountContact;
use Civi\Api4\AccountInvoice;
use CRM_Accountsync_ExtensionUtil as E;
use Psr\Log\LogLevel;

class CRM_Accountsync_Check {

  /**
   * @var array
   */
  private $messages;

  /**
   * constructor.
   *
   * @param $messages
   */
  public function __construct($messages) {
    $this->messages = $messages;
  }

  /**
   * Check no rows have NULL for connector_id.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function checkRequirements(): array {
    $this->checkNullConnectorID();
    return $this->messages;
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function checkNullConnectorID(): void {
    $accountContact = AccountContact::get(FALSE)
      ->addWhere('connector_id', 'IS NULL')
      ->execute();
    $count = $accountContact->count();

    if (!empty($count)) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'account_sync_account_contact',
        E::ts('There are %1 records in the `civicrm_account_contact` table which have a NULL connector_id. These need updating manually to 0 or the connector ID if using the connectors extension.',
          [
            1 => $count,
          ]
        ),
        E::ts('AccountSync: Database issues'),
        LogLevel::ERROR,
        'fa-database'
      );
      $this->messages[] = $message;
    }

    $accountInvoice = AccountInvoice::get(FALSE)
      ->addWhere('connector_id', 'IS NULL')
      ->execute();
    $count = $accountInvoice->count();

    if (!empty($count)) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'accountsync_account_invoice',
        E::ts('There are %1 records in the `civicrm_account_invoice` table which have a NULL connector_id. These need updating manually to 0 or the connector ID if using the connectors extension.',
          [
            1 => $count,
          ]
        ),
        E::ts('AccountSync: Database issues'),
        LogLevel::ERROR,
        'fa-database'
      );
      $this->messages[] = $message;
    }
  }

}
