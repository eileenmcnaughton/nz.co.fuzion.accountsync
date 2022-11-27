<?php
return [
  [
    'name' => 'SavedSearch_AccountInvoice_Synchronization_Errors',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountInvoice_Synchronization_Errors',
        'label' => 'Invoice Synchronization Errors',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'AccountInvoice',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contribution_id',
            'accounts_invoice_id',
            'error_data',
            'last_sync_date',
          ],
          'orderBy' => [],
          'where' => [
            [
              'error_data',
              'NOT LIKE',
              '%error_cleared%',
            ],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_AccountInvoice_Synchronization_Errors_SearchDisplay_AccountInvoice_Synchronization_Errors_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountInvoice_Synchronization_Errors',
        'label' => 'Invoice Synchronization Errors',
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
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => 'id',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contribution_id',
              'dataType' => 'Integer',
              'label' => 'Contribution ID',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'accounts_invoice_id',
              'dataType' => 'String',
              'label' => 'accounts_invoice_id',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'error_data',
              'dataType' => 'Text',
              'label' => 'Account Error Data',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'last_sync_date',
              'dataType' => 'Timestamp',
              'label' => 'Last Synchronization Date',
              'sortable' => TRUE,
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];