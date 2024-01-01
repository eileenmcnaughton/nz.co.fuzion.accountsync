<?php

use CRM_Accountsync_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Non_queued_Contributions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Non_queued_Contributions',
        'label' => E::ts('Non Queued Contributions'),
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contact_id.display_name',
            'total_amount',
            'contribution_status_id:label',
            'receive_date',
          ],
          'orderBy' => [],
          'where' => [
            [
              'contribution_status_id:name',
              'IN',
              [
                'Completed',
                'Pending',
              ],
            ],
            [
              'is_test',
              '=',
              FALSE,
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'AccountInvoice AS Contribution_AccountInvoice_contribution_id_01',
              'EXCLUDE',
              [
                'id',
                '=',
                'Contribution_AccountInvoice_contribution_id_01.contribution_id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Non_queued_Contributions_SearchDisplay_Non_queued_Contributions',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Non_queued_Contributions',
        'label' => E::ts('Non Queued Contributions'),
        'saved_search_id.name' => 'Non_queued_Contributions',
        'type' => 'table',
        'settings' => [
          'description' => E::ts("These contributions are not present in the Account Invoice Table. Therefore, they are not in the queue to be pushed to the external accounting system."),
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('Contribution ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Contact Display Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'total_amount',
              'dataType' => 'Money',
              'label' => E::ts('Total Amount'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contribution_status_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Contribution Status'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'receive_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Contribution Date'),
              'sortable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'Contribution',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('View Contribution'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Contribution',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Update Contribution'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Contribution',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete Contribution'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
