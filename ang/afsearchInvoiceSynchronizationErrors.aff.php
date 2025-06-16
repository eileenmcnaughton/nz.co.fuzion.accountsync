<?php
use CRM_Accountsync_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Invoice Synchronization Errors'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/accounting/errors/invoices',
  'permission' => [
    'administer CiviCRM system',
  ],
];
