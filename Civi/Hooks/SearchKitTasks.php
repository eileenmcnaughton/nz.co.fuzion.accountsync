<?php

namespace Civi\Hooks;

use CRM_Accountsync_ExtensionUtil as E;

class SearchKitTasks {

  /**
   * Add reset & queue actions to display.
   *
   * These are convenience actions that pre-set values to update in the api.
   *
   * @param array $tasks
   * @param bool $checkPermissions
   * @param int|null $userID
   * @param array $search
   * @param array $display
   */
  public function run(array &$tasks, bool $checkPermissions, ?int $userID, $search = [], $display = []): void {
    // $search and $display were added in 5.57: https://github.com/civicrm/civicrm-core/pull/25123
    if (!empty($display) && isset($display['name'])) {
      switch ($display['name']) {
        case 'AccountContact_Synchronization_Errors_Display':
        case 'AccountInvoice_Synchronization_Errors_Display':
          foreach (['AccountContact', 'AccountInvoice'] as $entity) {
            $tasks[$entity]['queue'] = [
              'title' => E::ts('Retry sync'),
              'apiBatch' => [
                'action' => 'update',
                'params' => ['values' => ['accounts_needs_update' => TRUE, 'error_data' => '', 'is_error_resolved' => TRUE]],
                'runMsg' => E::ts('Processing ...'),
                'successMsg' => E::ts('Successfully marked %1 records to retry next time the sync job runs.'),
                'errorMsg' => E::ts('An error occurred while attempting to mark %1 records for retry.'),
              ],
            ];
            $tasks[$entity]['dismiss'] = [
              'title' => E::ts('Mark as resolved'),
              'apiBatch' => [
                'action' => 'update',
                'params' => ['values' => ['is_error_resolved' => TRUE]],
                'runMsg' => E::ts('Processing ...'),
                'successMsg' => E::ts('Successfully marked %1 records as resolved.'),
                'errorMsg' => E::ts('An error occurred while marking errors as resolved.'),
              ],
            ];
            $tasks[$entity]['donotsync'] = [
              'title' => E::ts('Do not sync'),
              'apiBatch' => [
                'action' => 'update',
                'params' => ['values' => ['do_not_sync' => TRUE, 'is_error_resolved' => TRUE]],
                'runMsg' => E::ts('Processing ...'),
                'successMsg' => E::ts('Successfully marked %1 records as "Do not Sync".'),
                'errorMsg' => E::ts('An error occurred while marking errors as "Do not Sync".'),
              ],
            ];
          }
          break;

        default:
          return;
      }
    }
  }

}
