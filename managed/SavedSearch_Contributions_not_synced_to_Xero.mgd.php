<?php

use CRM_Accountsync_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Contributions_not_synced_to_Xero',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contributions_not_synced_to_Xero',
        'label' => E::ts('Contributions not in queue to Xero'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
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
        'expires_date' => NULL,
        'description' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
