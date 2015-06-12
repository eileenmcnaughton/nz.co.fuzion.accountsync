<?php

class CRM_Accountsync_BAO_AccountInvoice extends CRM_Accountsync_DAO_AccountInvoice {

  /**
   * Create a new AccountInvoice based on array-data.
   *
   * @param array $params key-value pairs
   *
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

  /**
   * Get a 'complex' invoice.
   *
   * Only call this via the api... I made it public static because of the move away from 'real' BAO
   * classes in preparation for doctrine but the api is the way to go.
   *
   * @param array $params
   *
   * @return array
   */
  public static function getDerived($params) {
    try{
      // ok this chaining is a bit heavy but it would be good to work towards API returning this more efficiently
      // ie. not keen on the fact you can't easily get line items for contributions related to participants
      // & TBH the communication with Xero takes 90% of the script time ...
      // @todo - set return properties on contribution.get
      // @todo at the moment we use getsingle because we are only dealing with 1 but should alter to 'get'
      // as in theory any params could be passed in - resulting in many - there are some api
      // issues around getfields to resolve though - see notes on api
      $contribution = civicrm_api3('contribution', 'getsingle', array_merge(array(
        'api.participant_payment.get' => array(
          'return' => 'api.participant., participant_id',
          'api.participant.get' => array(
            'api.line_item.get' => 1,
            'return' => 'participant_source, event_id, financial_type_id',
          ),
        ),
      ), $params));
      // There is a chaining bug on line item because chaining passes contribution_id along as entity_id.
      // CRM-16522.
      $contribution['api.line_item.get'] = civicrm_api3('line_item', 'get', array(
        'contribution_id' => $contribution['id'],
      ));

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
        $contributionAccountsContactIDs[$lineItem['accounts_contact_id']] = TRUE;
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
      //@todo move the getAccountingCode to a fn that caches it
      $contribution['accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($contribution['financial_type_id']);
      $contribution['accounts_contact_id'] = array_keys($contributionAccountsContactIDs);
    }
    catch(Exception $e) {
      // probably shouldn't catch & let calling class catch
    }

    // In 4.6 this might be more reliable as Monish did some tidy up on BAO_Search stuff.
    // Relying on it being unique makes me nervous...
    if (empty($contribution['payment_instrument_id'])) {
      $paymentInstruments = civicrm_api3('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
      $contribution['payment_instrument_id'] = array_search($contribution['payment_instrument'], $paymentInstruments['values']);
    }

    $instrumentFinancialAccounts = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount();
    $contribution['payment_instrument_financial_account_id'] = $instrumentFinancialAccounts[$contribution['payment_instrument_id']];
    try {
      $contribution['payment_instrument_accounting_code'] = civicrm_api3('financial_account', 'getvalue', array(
        'id' => $contribution['payment_instrument_financial_account_id'],
        'return' => 'accounting_code',
      ));
    }
    catch(Exception $e) {
      ;
    }

    return array($contribution['id'] => $contribution);
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
      $invoice['line_items'] = array();
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
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($invoice['participant']['id'], 'Integer')));
      while ($dao->fetch()) {
        $lineItems = civicrm_api3('line_item', 'get', array(
          'sequential' => 1,
          'entity_id' => $dao->id,
          'entity_table' => 'civicrm_participant',
        ));
        $invoice['line_items'][] = array_merge($lineItems['values'][0], array('contact_id' => $dao->contact_id, 'display_name' => $dao->display_name));
      }
    }
  }

  /**
   * Update contributions in civicrm based on their status in Xero.
   */
  public static function completeContributionFromAccountsStatus() {
    $sql = "
      SELECT contribution_id
      FROM civicrm_account_invoice cas
      LEFT JOIN civicrm_contribution  civi ON cas.contribution_id = civi.id
      WHERE civi.contribution_status_id =2
      AND accounts_status_id = 1
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      civicrm_api3('contribution', 'completetransaction', array('id' => $dao->contribution_id));
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
      WHERE civi.contribution_status_id =2
      AND accounts_status_id =3
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $params['contribution_status_id'] = 3;
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
    static $contacts = array();
    if (!in_array($financialTypeID, $contacts)) {
      $accountingCode = self::getAccountCode($financialTypeID);
      $contacts[$financialTypeID] = CRM_Core_DAO::singleValueQuery(
        "SELECT contact_id FROM civicrm_financial_account
         WHERE accounting_code = %1
        ",
        array(1 => array($accountingCode, 'String'))
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
   */
  public static function getAccountCode($financialTypeID) {
    static $codes = array();
    if (!in_array($financialTypeID, $codes)) {
      $codes[$financialTypeID] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialTypeID);
    }
    return $codes[$financialTypeID];
  }

}
