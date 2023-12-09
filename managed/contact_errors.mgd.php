<?php

use CRM_Accountsync_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_AccountContact_Synchronization_Errors',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountContact_Synchronization_Errors',
        'label' => E::ts('Contact Synchronization Errors'),
        'api_entity' => 'AccountContact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contact_id.display_name',
            'contact_id',
            'accounts_contact_id',
            'error_data',
            'last_sync_date',
          ],
          'orderBy' => [
            'id DESC',
          ],
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
    'name' => 'SavedSearch_AccountContact_Synchronization_Errors_SearchDisplay_AccountContact_Synchronization_Errors_Display',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AccountContact_Synchronization_Errors_Display',
        'label' => E::ts('Contact Synchronization Errors'),
        'saved_search_id.name' => 'AccountContact_Synchronization_Errors',
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
              'label' => E::ts('id'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Contact'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id',
              'dataType' => 'Integer',
              'label' => E::ts('CiviCRM Contact ID'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '_blank',
              ],
              'title' => E::ts('View CiviCRM Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'accounts_contact_id',
              'dataType' => 'String',
              'label' => E::ts('Accounts Contact ID'),
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
          ],
          'headerCount' => TRUE,
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
