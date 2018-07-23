<?php

class CRM_Accountsync_BAO_Config extends CRM_Accountsync_DAO_AccountContact {


  public static function getSupportedContributionEntities() {
    return [
      'Contact' => 'Contact',
      'Contribution' => 'Contribution',
      'ContributionRecur' => 'Recurring Contribution',
    ];
  }

  public static function getSupportedContributionCreateEntities() {
    return [
      'Contribution' => 'Contribution',
    ];
  }

  public static function getSupportedContactUpdateEntities() {
    return [
      'Contact' => 'Contact',
      'Contribution' => 'Contribution',
      'ContributionRecur' => 'Recurring Contribution',
      'Address' => 'Address',
      'Email' => 'Email',
      'Phone' => 'Phone',
    ];
  }

  /**
   * Get payment processors.
   *
   * This differs from the option value in that we append description for disambiguation.
   *
   * @return array
   */
  public static function getPaymentProcessors() {
    $results = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 0,
      'is_test' => 0,
      'return' => ['id', 'name', 'description', 'domain_id'],
    ));

    $processors = array();
    foreach ($results['values'] as $processorID => $details) {
      $processors[$processorID] = $details['name'];
      if (!empty($details['description'])) {
        $processors[$processorID] .= ' : ' . $details['description'];
      }
    }
    return $processors;
  }

}