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
   */
  public function run(array &$tasks, bool $checkPermissions, ?int $userID): void {
    foreach (['AccountContact', 'AccountInvoice'] as $entity) {
      $tasks[$entity]['queue'] = [
        'title' => E::ts('Re-queue %1', [1 => 'record']),
        'apiBatch' => [
          'action' => 'update',
          'params' => ['values' => ['accounts_needs_update' => TRUE, 'error_data' => '']],
          'runMsg' => E::ts('Queueing synchronization ...'),
          'successMsg' => E::ts('Successfully re-queued %1 record.'),
          'errorMsg' => E::ts('An error occurred while attempting to queue the record.'),
        ],
      ];
      $tasks[$entity]['dismiss'] = [
        'title' => E::ts('Dismiss error %1', [1 => 'record']),
        'apiBatch' => [
          'action' => 'update',
          'params' => ['values' => ['is_error_resolved' => TRUE]],
          'runMsg' => E::ts('Dismissing error ...'),
          'successMsg' => E::ts('Successfully dismissed error for %1 record.'),
          'errorMsg' => E::ts('An error occurred while attempting to dismiss the error.'),
        ],
      ];
    }
  }

}