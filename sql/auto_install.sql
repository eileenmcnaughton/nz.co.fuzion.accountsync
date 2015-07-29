DROP TABLE IF EXISTS `civicrm_account_contact`;
DROP TABLE IF EXISTS `civicrm_invoice_contact`;

-- /*******************************************************
-- *
-- * civicrm_account_contact
-- *
-- * Synchronization of accounts contacts
-- *
-- *******************************************************/
CREATE TABLE `civicrm_account_contact` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique Entity Setting ID',
  `contact_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `accounts_contact_id` VARCHAR(128) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
  `last_sync_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the contact last synced.',
  `accounts_modified_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'When was the invoice last Altered in the accounts system.',
  `accounts_display_name` VARCHAR(128) NULL DEFAULT NULL COMMENT 'Name from Accounts Package' COLLATE 'utf8_unicode_ci',
  `accounts_data` TEXT NULL COMMENT 'json array of data as returned from accounts system' COLLATE 'utf8_unicode_ci',
  `error_data` TEXT NULL COMMENT 'json array of error data' COLLATE 'utf8_unicode_ci',
  `plugin` VARCHAR(32) NOT NULL COMMENT 'Plugin creating the account' COLLATE 'utf8_unicode_ci',
  `connector_id` INT(11) NULL DEFAULT NULL COMMENT 'ID of connector. Relevant to connect to more than one account of the same type',
  `accounts_needs_update` TINYINT(4) NULL DEFAULT '1' COMMENT 'Include in next push to accounts',
  `do_not_sync` TINYINT(4) NULL DEFAULT '0' COMMENT 'Do Not Sync',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `account_system_id` (`accounts_contact_id`, `connector_id`, `plugin`),
  UNIQUE INDEX `contact_id_plugin` (`contact_id`, `connector_id`, `plugin`)
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;

CREATE TABLE `civicrm_account_invoice` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique Entity Setting ID',
  `contribution_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `accounts_invoice_id` VARCHAR(128) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
  `last_sync_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the contact last synced.',
  `accounts_modified_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'When was the invoice last Altered in the accounts system.',
  `accounts_status_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `accounts_data` TEXT NULL COMMENT 'json array of data as returned from accounts system' COLLATE 'utf8_unicode_ci',
  `error_data` TEXT NULL COMMENT 'json array of error data' COLLATE 'utf8_unicode_ci',
  `plugin` VARCHAR(32) NOT NULL COMMENT 'Plugin creating the account' COLLATE 'utf8_unicode_ci',
  `connector_id` INT(11) NULL DEFAULT NULL COMMENT 'ID of connector. Relevant to connect to more than one account of the same type',
  `accounts_needs_update` TINYINT(4) NULL DEFAULT '0' COMMENT 'Include in next push to accounts',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `account_system_id` (`accounts_invoice_id`, `plugin`),
  UNIQUE INDEX `invoice_id_plugin` (`contribution_id`, `plugin`)
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;
