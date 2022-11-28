<?php

/**
 * Collection of upgrade steps
 */
class CRM_Accountsync_Upgrader extends CRM_Accountsync_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   */
  public function upgrade_1000(): bool {
    $this->ctx->log->info('Applying update 1000');
    CRM_Core_DAO::executeQuery("
ALTER TABLE `civicrm_account_contact`
  ADD COLUMN `connector_id` INT NULL COMMENT 'ID of connector. Relevant to connect to more than one account of the same type' AFTER `accounts_needs_update`,
  DROP INDEX `account_system_id`,
  ADD UNIQUE INDEX `account_system_id` (`accounts_contact_id`, `connector_id`, `plugin`),
  DROP INDEX `contact_id_plugin`,
  ADD UNIQUE INDEX `contact_id_plugin` (`contact_id`, `connector_id`, `plugin`);
");

    CRM_Core_DAO::executeQuery("
  ALTER TABLE `civicrm_account_invoice`
  ADD COLUMN `connector_id` INT NULL COMMENT 'ID of connector. Relevant to connect to more than one account of the same type' AFTER `accounts_needs_update`,
  DROP INDEX `account_system_id`,
  ADD UNIQUE INDEX `account_system_id` (`accounts_invoice_id`, `connector_id`, `plugin`),
  DROP INDEX `invoice_id_plugin`,
  ADD UNIQUE INDEX `invoice_id_plugin` (`contribution_id`, `connector_id`, `plugin`)
    ");
    return TRUE;
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   */
  public function upgrade_1100(): bool {
    $this->ctx->log->info('Applying update 1100');
    CRM_Core_DAO::executeQuery("
ALTER TABLE `civicrm_account_contact`
  ADD COLUMN `do_not_sync` TINYINT(4) DEFAULT 0 COMMENT 'Do not sync this contact' AFTER `accounts_needs_update`
");
    return TRUE;
  }

  /**
   * Change accounts_status_id to have a default of 0.
   *
   * It was previously NULL but then a query like accounts_status_id IN ()
   *  would not get unset rows.
   *
   * @return TRUE on success
   */
  public function upgrade_1200(): bool {
    $this->ctx->log->info('Applying update 1200');
    CRM_Core_DAO::executeQuery('
ALTER TABLE `civicrm_account_invoice`
  ALTER COLUMN `accounts_status_id` SET DEFAULT 0
');
    return TRUE;
  }

  /**
   * Change existing accounts_status_id to 0 for NULL values.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1300(): bool {
    $this->ctx->log->info('Applying update 1300');
    CRM_Core_DAO::executeQuery('UPDATE `civicrm_account_invoice` SET `accounts_status_id` = 0 WHERE `accounts_status_id` IS NULL');
    return TRUE;
  }

  /**
   * Set connector_id default to 0, add is_error_resolved.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1400(): bool {
    $this->ctx->log->info('Set default for connector_id to 0, add is_error_resolved');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_account_contact ALTER connector_id SET DEFAULT 0,
      ADD COLUMN `is_error_resolved` tinyint DEFAULT 0 COMMENT 'Filter out if resolved'"
    );
    CRM_Core_DAO::executeQuery(
      "ALTER TABLE civicrm_account_invoice ALTER connector_id SET DEFAULT 0,
       ADD COLUMN `is_error_resolved` tinyint DEFAULT 0 COMMENT 'Filter out if resolved'"
    );
    CRM_Core_DAO::executeQuery('UPDATE civicrm_account_invoice SET is_error_resolved = 1 WHERE error_data LIKE "error_cleared%"');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_account_contact SET is_error_resolved = 1 WHERE error_data LIKE "error_cleared%"');
    CRM_Core_DAO::executeQuery('
UPDATE `civicrm_account_invoice` main
  LEFT JOIN civicrm_account_invoice duplicate
    ON main.contribution_id = duplicate.contribution_id
      AND main.connector_id IS NULL
      AND (duplicate.connector_id = 0 OR duplicate.connector_id IS NULL)
      AND duplicate.id > main.id
SET main.connector_id = 0 WHERE duplicate.id IS NULL AND main.connector_id IS NULL');
    return TRUE;
  }

}
