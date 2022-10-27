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

use CRM_Accountsync_ExtensionUtil as E;

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
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function checkRequirements() {
    $this->checkNullConnectorID();
    return $this->messages;
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function checkNullConnectorID() {
    $accountContact = \Civi\Api4\AccountContact::get(FALSE)
      ->addWhere('connector_id', 'IS NULL')
      ->execute();
    $count = $accountContact->count();

    if (!empty($count)) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__ . E::SHORT_NAME . '_accountcontact',
        E::ts('There are %1 records in the `civicrm_account_contact` table which have a NULL connector_id. These need updating manually to 0 or the connector ID if using the connectors extension.',
          [
            1 => $count,
          ]
        ),
        E::ts('AccountSync: Database issues'),
        \Psr\Log\LogLevel::ERROR,
        'fa-database'
      );
      $this->messages[] = $message;
    }

    $accountInvoice = \Civi\Api4\AccountInvoice::get(FALSE)
      ->addWhere('connector_id', 'IS NULL')
      ->execute();
    $count = $accountInvoice->count();

    if (!empty($count)) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__ . E::SHORT_NAME . '_accountinvoice',
        E::ts('There are %1 records in the `civicrm_account_invoice` table which have a NULL connector_id. These need updating manually to 0 or the connector ID if using the connectors extension.',
          [
            1 => $count,
          ]
        ),
        E::ts('AccountSync: Database issues'),
        \Psr\Log\LogLevel::ERROR,
        'fa-database'
      );
      $this->messages[] = $message;
    }
  }

}
