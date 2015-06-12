<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CiviAccountSync Complete Contributions From Accounts',
    'entity' => 'Job',
    'params' =>
    array (
      'version' => 3,
      'name' => 'CiviAccountSync Complete Contributions',
      'description' => 'Complete Contributions in CiviCRM where completed in Accounts',
      'api_entity' => 'AccountInvoice',
      'api_action' => 'update_contribution',
      'run_frequency' => 'Always',
      'parameters' => 'plugin=xero
accounts_status_id=1',
    ),
  ),
  1 =>
  array (
    'name' => 'CiviAccountSync Cancel Contributions From Accounts',
    'entity' => 'Job',
    'params' =>
    array (
      'version' => 3,
      'name' => 'CiviAccountSync Cancel Contributions',
      'description' => 'Cancel Contributions in CiviCRM where cancelled in Accounts',
      'api_entity' => 'AccountInvoice',
      'api_action' => 'update_contribution',
      'run_frequency' => 'Always',
      'parameters' => 'plugin=xero
       accounts_status_id=3',
    ),
  ),
);
