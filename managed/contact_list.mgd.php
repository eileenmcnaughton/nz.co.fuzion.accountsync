<?php

use CRM_Accountsync_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Accountsync_Contact_list',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Accountsync_Contact_list',
        'label' => E::ts('Accountsync Contact list'),
        'api_entity' => 'AccountContact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contact_id',
            'accounts_contact_id',
            'contact_id.display_name',
            'accounts_display_name',
            'plugin',
            'do_not_sync',
            'is_error_resolved',
            'accounts_needs_update',
          ],
          'orderBy' => [],
          'where' => [],
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
    'name' => 'SavedSearch_Accountsync_Contact_list_SearchDisplay_Accountsync_Contact_list',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Accountsync_Contact_list',
        'label' => E::ts('Accountsync Contact list'),
        'saved_search_id.name' => 'Accountsync_Contact_list',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('ID'),
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
              'title' => E::ts('View CiviCRM Contact ID'),
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
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('CiviCRM Display Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '_blank',
              ],
              'title' => E::ts('View CiviCRM Contact ID'),
            ],
            [
              'type' => 'field',
              'key' => 'accounts_display_name',
              'dataType' => 'String',
              'label' => E::ts('Accounts Display Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'plugin',
              'dataType' => 'String',
              'label' => E::ts('Accounts'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'do_not_sync',
              'dataType' => 'Boolean',
              'label' => E::ts('Do Not Sync'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_error_resolved',
              'dataType' => 'Boolean',
              'label' => E::ts('Error Resolved'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'accounts_needs_update',
              'dataType' => 'Boolean',
              'label' => E::ts('Accounts Needs Update'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
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
