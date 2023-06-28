<?php

use CRM_AccountSync_ExtensionUtil as E;

class CRM_Accountsync_BAO_AccountInvoice extends CRM_Accountsync_DAO_AccountInvoice {

  /**
   * Create a new AccountInvoice based on array-data.
   *
   * @param array $params key-value pairs
   *
   * @return CRM_Accountsync_DAO_AccountInvoice|NULL
   */
  public static function create(array $params) {
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

  /**
   * Get a 'complex' invoice.
   *
   * Only call this via the api... I made it public static because of the move
   * away from 'real' BAO classes in preparation for doctrine but the api is
   * the way to go.
   *
   * @param array $params
   *
   * @return array
   */
  public static function getDerived($params) {
    try {
      // ok this chaining is a bit heavy but it would be good to work towards API returning this more efficiently
      // ie. not keen on the fact you can't easily get line items for contributions related to participants
      // & TBH the communication with Xero takes 90% of the script time ...
      // @todo - set return properties on contribution.get
      // @todo at the moment we use getsingle because we are only dealing with 1 but should alter to 'get'
      // as in theory any params could be passed in - resulting in many - there are some api
      // issues around getfields to resolve though - see notes on api
      $contribution = civicrm_api3('contribution', 'getsingle', array_merge([
        'api.participant_payment.get' => [
          'return' => 'api.participant., participant_id',
          'api.participant.get' => [
            'api.line_item.get' => 1,
            'return' => 'participant_source, event_id, financial_type_id',
          ],
        ],
      ], $params));
      $accountContact = \Civi\Api4\AccountContact::get(FALSE)
        ->addSelect('accounts_contact_id')
        ->addWhere('contact_id', '=', $contribution['contact_id'])
        ->execute()
        ->first();
      if (!empty($accountContact['accounts_contact_id'])) {
        $contribution['accounts_contact_id'] = $accountContact['accounts_contact_id'];
      }

      // There is a chaining bug on line item because chaining passes contribution_id along as entity_id.
      // CRM-16522.
      $contribution['api.line_item.get'] = civicrm_api3('line_item', 'get', [
        'contribution_id' => $contribution['id'],
        'options' => ['limit' => 0],
      ]);

      if ($contribution['api.line_item.get']['count']) {
        $contribution['line_items'] = $contribution['api.line_item.get']['values'];
      }
      else {
        //we'll keep the participant record for anyone trying to do hooks
        $contribution['participant'] = $contribution['api.participant_payment.get']['values'][0]['api.participant.get']['values'][0];
        $contribution['line_items'] = $contribution['participant']['api.line_item.get']['values'];
        //if multiple participants one line item each
        self::_getAdditionalParticipanLineItems($contribution);
      }

      foreach ($contribution['line_items'] as &$lineItem) {
        $lineItem['accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($lineItem['financial_type_id']);
        $lineItem['accounts_contact_id'] = self::getAccountsContact($lineItem['financial_type_id']);
        if (!isset($lineItem['contact_id'])) {
          //this would have been set for a secondary participant above so we are ensuring primary ones have it
          // for conformity & ease downstream
          $lineItem['contact_id'] = $contribution['contact_id'];
        }
        if (!isset($lineItem['display_name'])) {
          //this would have been set for a secondary participant above so we are ensuring primary ones have it
          // for conformity & ease downstream
          $lineItem['display_name'] = $contribution['display_name'];
        }
      }
    }
    catch (Exception $e) {
      // probably shouldn't catch & let calling class catch
      \Civi::log()->error('AccountInvoice.Getderived: ' . $e->getMessage());
    }

    // In 4.6 this might be more reliable as Monish did some tidy up on BAO_Search stuff.
    // Relying on it being unique makes me nervous...
    if (empty($contribution['payment_instrument_id'])) {
      $paymentInstruments = civicrm_api3('contribution', 'getoptions', ['field' => 'payment_instrument_id']);
      $contribution['payment_instrument_id'] = array_search($contribution['payment_instrument'], $paymentInstruments['values']);
    }

    try {
      $contribution['payment_instrument_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($contribution['payment_instrument_id']);
      $contribution['payment_instrument_accounting_code'] = civicrm_api3('financial_account', 'getvalue', [
        'id' => $contribution['payment_instrument_financial_account_id'],
        'return' => 'accounting_code',
      ]);
    }
    catch (Exception $e) {
      \Civi::log()->error('AccountInvoice.Getderived: ' . $e->getMessage());
    }

    return [$contribution['id'] => $contribution];
  }

  /**
   * Get Line items for invoice.
   *
   * At this stage only secondary participants are being fetched here.
   *
   * @param array $invoice Invoice array being prepared for Xero
   *
   * @return array|null
   *   Line items if there are some else null
   */
  protected static function _getAdditionalParticipanLineItems(&$invoice) {
    $rowsTotal = 0;
    if (!is_array($invoice['line_items'])) {
      // this seems to occur when the participant record has been deleted & not the contribution record
      $invoice['line_items'] = [];
      return;
    }
    foreach ($invoice['line_items'] as $line_item) {
      $rowsTotal .= $line_item['line_total'];
    }

    if ($invoice['total_amount'] > $rowsTotal) {
      // api let us down use direct sql
      // @TODO get api to accept the below
      // $otherParticipants = civicrm_api('Participant','Get',array('version' =>3,'participant_registered_by_id' =>$invoice['participant_id']));
      $sql = "SELECT p.id, contact_id, display_name FROM civicrm_participant p
      LEFT JOIN civicrm_contact c ON c.id = p.contact_id
      WHERE registered_by_id = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, [
        1 => [
          $invoice['participant']['id'],
          'Integer',
        ],
      ]);
      while ($dao->fetch()) {
        $lineItems = civicrm_api3('line_item', 'get', [
          'sequential' => 1,
          'entity_id' => $dao->id,
          'entity_table' => 'civicrm_participant',
        ]);
        $invoice['line_items'][] = array_merge($lineItems['values'][0], [
          'contact_id' => $dao->contact_id,
          'display_name' => $dao->display_name,
        ]);
      }
    }
  }

  /**
   * Update contributions in civicrm based on their status in Xero.
   */
  public static function completeContributionFromAccountsStatus() {
    $sql = "
      SELECT cas.id civicrm_account_invoice_id, cas.contribution_id, cc.receive_date, cc.total_amount
      FROM civicrm_account_invoice cas
      LEFT JOIN civicrm_contribution cc ON cas.contribution_id = cc.id
      WHERE cc.contribution_status_id = %1
      AND accounts_status_id = %2
      ";

    $queryParams = [
      1 => [
        CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
        'Integer',
      ],
      2 => [
        CRM_Core_PseudoConstant::getKey('CRM_Accountsync_BAO_AccountInvoice', 'accounts_status_id', 'completed'),
        'Integer',
      ]
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    $paymentParams = [];
    // We are receiving directly from contribution table so it will be well formatted.
    $paymentParams['skipCleanMoney'] = TRUE;
    // Get send receipt override
    switch (Civi::settings()->get('account_sync_send_receipt')) {
      case 'send':
        $paymentParams['is_send_contribution_notification'] = 1;
        break;

      case 'do_not_send':
        $paymentParams['is_send_contribution_notification'] = 0;
        break;

      case 'no_override':
      default:
        break;
    }

    while ($dao->fetch()) {
      $paymentParams['contribution_id'] = $dao->contribution_id;
      // @fixme receive_date/total amount should be retrieved from accounts_data otherwise they may not be accurate.
      //   But this requires plugin specific parsing? eg. accounts_data is different for Xero and Quickbooks?
      $paymentParams['trxn_date'] = $dao->receive_date;
      $paymentParams['total_amount'] = $dao->total_amount;
      try {
        civicrm_api3('Payment', 'create', $paymentParams);
      }
      catch (CiviCRM_API3_Exception $e) {
        // CiviCRM failed to complete the contribution.
        $error = 'AccountSync completeContributionFromAccountsStatus failed: ' . $e->getMessage();
        civicrm_api3('AccountInvoice', 'create', [
          'id' => $dao->civicrm_account_invoice_id,
          'error_data' => json_encode([$error]),
          'is_error_resolved' => 0,
        ]);
      }
    }
  }

  /**
   * Cancel contribution in Civi based on Xero Status.
   *
   * @todo - I don't believe this will adequately cancel related entities
   */
  public static function cancelContributionFromAccountsStatus($params) {
    //get pending registrations
    $sql = "SELECT  cas.contribution_id
      FROM civicrm_account_invoice cas
      LEFT JOIN civicrm_contribution  civi ON cas.contribution_id = civi.id
      WHERE contribution_status_id != %1 AND accounts_status_id = %2
    ";

    $cancelledContributionStatus = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled');
    $queryParams = [
      1 => [
        $cancelledContributionStatus,
        'Integer',
      ],
      2 => [
        CRM_Core_PseudoConstant::getKey('CRM_Accountsync_BAO_AccountInvoice', 'accounts_status_id', 'cancelled'),
        'Integer',
      ]
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    while ($dao->fetch()) {
      $params['contribution_status_id'] = $cancelledContributionStatus;
      $params['id'] = $dao->contribution_id;
      civicrm_api3('Contribution', 'Create', $params);
    }
  }

  /**
   * Get the financial_contact from the financial type id.
   *
   * @param int $financialTypeID
   *
   * @return mixed
   */
  public static function getAccountsContact($financialTypeID) {
    static $contacts = [];
    if (empty($contacts[$financialTypeID])) {
      $accountingCode = self::getAccountCode($financialTypeID);
      $contacts[$financialTypeID] = CRM_Core_DAO::singleValueQuery(
        "SELECT contact_id FROM civicrm_financial_account
         WHERE accounting_code = %1
        ",
        [1 => [$accountingCode, 'String']]
      );
    }
    return $contacts[$financialTypeID];
  }

  /**
   * Get the accounting code from the financial type id.
   *
   * @param int $financialTypeID *
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getAccountCode($financialTypeID) {
    static $codes = [];
    if (!in_array($financialTypeID, $codes)) {
      $codes[$financialTypeID] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialTypeID);
    }
    if ($codes[$financialTypeID] === NULL) {
      throw new CRM_Core_Exception("No Income Account code configured for financial type $financialTypeID");
    }
    return $codes[$financialTypeID];
  }

  /**
   * Get the options for the receipting setting.
   *
   * @return string[]
   */
  public static function receiptOptions(): array {
    return [
      'no_override' => 'No override',
      'send' => 'Send',
      'do_not_send' => 'Do not send',
    ];
  }

  /**
   * Get the options for the receipting setting.
   *
   * @return string[]
   */
  public static function getAccountStatuses(): array {
    return [
      ['id' => 0, 'name' => 'unknown', 'label' => E::ts('Unknown')],
      ['id' => 1, 'name' => 'completed', 'label' => E::ts('Completed')],
      ['id' => 2, 'name' => 'pending', 'label' => E::ts('Pending')],
      ['id' => 3, 'name' => 'cancelled', 'label' => E::ts('Cancelled')],
    ];
  }

}
