<?php

use CRM_Accountsync_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Accountsync_Contact_Match',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Accountsync_Contact_Match',
        'label' => E::ts('Accountsync Contact Match'),
        'api_entity' => 'AccountContact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'contact_id',
            'accounts_contact_id',
            'do_not_sync',
            'AccountContact_Contact_contact_id_01.id',
            'accounts_display_name',
            'plugin',
            'accounts_modified_date',
          ],
          'orderBy' => [],
          'where' => [
            [
              'OR',
              [
                [
                  'contact_id.display_name',
                  'IS EMPTY',
                ],
                [
                  'accounts_contact_id',
                  'IS EMPTY',
                ],
              ],
            ],
            [
              'do_not_sync',
              '=',
              FALSE,
            ],
            [
              'AccountContact_Contact_contact_id_01.is_deleted',
              'IS EMPTY',
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'Contact AS AccountContact_Contact_contact_id_01',
              'LEFT',
              [
                'contact_id',
                '=',
                'AccountContact_Contact_contact_id_01.id',
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
    'name' => 'SavedSearch_Accountsync_Contact_Match_SearchDisplay_Accountsync_Contact_Match_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Accountsync_Contact_Match_Table_1',
        'label' => E::ts('Accountsync Contact Match Table 1'),
        'saved_search_id.name' => 'Accountsync_Contact_Match',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => FALSE,
          ],
          'placeholder' => 5,
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('AccountsContact ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id',
              'dataType' => 'Integer',
              'label' => E::ts('CiviCRM Contact ID'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'title' => E::ts('Click to match with an existing contact'),
            ],
            [
              'type' => 'field',
              'key' => 'accounts_contact_id',
              'dataType' => 'String',
              'label' => E::ts('Accounts system Contact ID'),
              'sortable' => TRUE,
              'link' => [
                'path' => 'https://go.xero.com/Contacts/View/[accounts_contact_id]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '_blank',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'do_not_sync',
              'dataType' => 'Boolean',
              'label' => E::ts('Do Not Sync'),
              'sortable' => TRUE,
              'editable' => TRUE,
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
              'label' => E::ts('Account Plugin'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'accounts_modified_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Accounts Modified Date'),
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
