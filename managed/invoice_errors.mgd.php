<?php

use CRM_Accountsync_ExtensionUtil as E;

$columns = [
  [
    'type' => 'field',
    'key' => 'id',
    'dataType' => 'Integer',
    'label' => E::ts('id'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'contribution_id',
    'dataType' => 'Integer',
    'label' => E::ts('Contribution ID'),
    'sortable' => TRUE,
    'link' => [
      'path' => '',
      'entity' => 'Contribution',
      'action' => 'view',
      'join' => 'contribution_id',
      'target' => 'crm-popup',
    ],
    'title' => E::ts('View Contribution'),
  ],
  [
    'type' => 'field',
    'key' => 'accounts_invoice_id',
    'dataType' => 'String',
    'label' => E::ts('Accounts Invoice ID'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'error_data',
    'dataType' => 'Text',
    'label' => E::ts('Account Error Data'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'last_sync_date',
    'dataType' => 'Timestamp',
    'label' => E::ts('Last Synchronization Date'),
    'sortable' => TRUE,
  ],
];
$connectors = _accountsync_get_connectors();
if (count($connectors) > 1) {
  $columns[] = [
    'type' => 'field',
    'key' => 'connector_id',
    'dataType' => 'Integer',
    'label' => 'connector_id',
    'sortable' => TRUE,
    'editable' => TRUE,
  ];
}

$searches = [
  [
    'name' => 'SavedSearch_AccountInvoice_Synchronization_Errors',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountInvoice_Synchronization_Errors',
        'label' => E::ts('Invoice Synchronization Errors'),
        'api_entity' => 'AccountInvoice',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contribution_id',
            'accounts_invoice_id',
            'error_data',
            'last_sync_date',
            'accounts_needs_update',
            'connector_id',
          ],
          'orderBy' => [],
          'where' => [
            [
              'is_error_resolved',
              '=',
              FALSE,
            ],
            [
              'error_data',
              'IS NOT EMPTY',
            ],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_AccountInvoice_Synchronization_Errors_SearchDisplay_AccountInvoice_Synchronization_Errors_Display',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountInvoice_Synchronization_Errors_Display',
        'label' => E::ts('Invoice Synchronization Errors'),
        'saved_search_id.name' => 'AccountInvoice_Synchronization_Errors',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [],
          'placeholder' => 5,
          'sort' => [],
          'columns' => $columns,
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];

return $searches;
