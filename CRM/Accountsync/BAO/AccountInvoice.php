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

  /**
   * Only call this via the api... I made it public static because of the move away from 'real' BAO
   * classes in preparation for doctrine but the api is the way to go
   * @param array $params
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
        'api.line_item.get' => 1,
        'api.participant_payment.get' => array(
          'return' => 'api.participant., participant_id',
          'api.participant.get' => array(
            'api.line_item.get' => 1,
            'return' => 'participant_source, event_id, financial_type_id',
          )
        )
      ), $params));
      if($contribution['api.line_item.get']['count']) {
        $contribution['line_items'] = $contribution['api.line_item.get']['values'];
      }
      else {
        //we'll keep the participant record for anyone trying to do hooks
        $contribution['participant'] = $contribution['api.participant_payment.get']['values'][0]['api.participant.get']['values'][0];
        $contribution['line_items'] = $contribution['participant']['api.line_item.get']['values'];
        //if muliple participants one line item each
        self::_getAdditionalParticipanLineItems($contribution);
      }
      foreach ($contribution['line_items'] as &$lineItem) {
        $lineItem['accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($lineItem['financial_type_id']);
        if(!isset($lineItem['contact_id'])) {
          //this would have been set for a secondary participant above so we are ensuring primary ones have it
          // for conformity & ease downstream
          $lineItem['contact_id'] = $contribution['contact_id'];
        }
        if(!isset($lineItem['display_name'])) {
          //this would have been set for a secondary participant above so we are ensuring primary ones have it
          // for conformity & ease downstream
          $lineItem['display_name'] = $contribution['display_name'];
        }
      }
      //@todo move the getAccountingCode to a fn that caches it
      $contribution['accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($contribution['financial_type_id']);
    }
    catch(Exception $e) {
      // probably shouldn't catch & let calling class catch
    }
    return array($contribution['id'] => $contribution);
  }

  /**
  * Get Line items for invoice. At this stage only secondary participants are being fetched here
  * @param array $invoice Invoice array being prepared for Xero
  * @return array $lineitems if there are some else null
  */
  static function _getAdditionalParticipanLineItems(&$invoice) {
    $rowsTotal = 0;
    foreach ($invoice['line_items'] as $line_item) {
      $rowsTotal .= $line_item['line_total'];
    }

    if ($invoice['total_amount'] > $rowsTotal) {
      // api let us down use direct sql
      // @TODO get api to accept the below
      //  $otherParticipants = civicrm_api('Participant','Get',array('version' =>3,'participant_registered_by_id' =>$invoice['participant_id']));
      $sql = "SELECT p.id, contact_id, display_name FROM civicrm_participant p
      LEFT JOIN civicrm_contact c ON c.id = p.contact_id
      WHERE registered_by_id = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($invoice['participant']['id'], 'Integer')));
      while ($dao->fetch()) {
        $lineitems = civicrm_api3('line_item', 'get', array('sequential' => 1, 'entity_id' => $dao->id, 'entity_table' => 'civicrm_participant'));
        $invoice['line_items'][] = array_merge($lineitems['values'][0], array('contact_id' => $dao->contact_id, 'display_name' => $dao->display_name));
      }
    }
  }

  /**
   * Update contributions in civicrm based on their status in Xero
   */
  static function completeContributionFromAccountsStatus($params) {
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
}
