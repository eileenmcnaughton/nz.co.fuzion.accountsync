-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_account_invoice`;
DROP TABLE IF EXISTS `civicrm_account_contact`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_account_contact
-- *
-- * Contacts Synced to Accounts package
-- *
-- *******************************************************/
CREATE TABLE `civicrm_account_contact` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique AccountContact ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `accounts_contact_id` varchar(128) COMMENT 'External Reference',
  `accounts_display_name` varchar(128) COMMENT 'Name from Accounts Package',
  `last_sync_date` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the contact last synced.',
  `accounts_modified_date` timestamp NULL COMMENT 'When was the invoice last Altered in the accounts system.',
  `accounts_data` text COMMENT 'json array of data as returned from accounts system',
  `error_data` text COMMENT 'json array of error data as returned from accounts system',
  `accounts_needs_update` tinyint DEFAULT 1 COMMENT 'Include in next push to accounts',
  `connector_id` int unsigned DEFAULT 0 COMMENT 'ID of connector. Relevant to connect to more than one account of the same type',
  `plugin` varchar(32) COMMENT 'Name of plugin creating the account',
  `do_not_sync` tinyint DEFAULT 0 COMMENT 'Do not sync this contact',
  `is_error_resolved` tinyint DEFAULT 0 COMMENT 'Filter out if resolved',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UI_account_system_id`(accounts_contact_id, connector_id, plugin),
  UNIQUE INDEX `UI_contact_id_plugin`(contact_id, connector_id, plugin),
  CONSTRAINT FK_civicrm_account_contact_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_account_invoice
-- *
-- * Account System Invoices
-- *
-- *******************************************************/
CREATE TABLE `civicrm_account_invoice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique AccountInvoice ID',
  `contribution_id` int unsigned DEFAULT NULL COMMENT 'FK to contribution table.',
  `accounts_invoice_id` varchar(128) DEFAULT NULL COMMENT 'External Reference',
  `accounts_status_id` int unsigned DEFAULT 0 COMMENT 'Status in accounts system (mapped to civicrm definition)',
  `last_sync_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the contact last synced.',
  `accounts_modified_date` timestamp NULL COMMENT 'When was the invoice last Altered in the accounts system.',
  `accounts_data` text DEFAULT NULL COMMENT 'json array of data as returned from accounts system',
  `error_data` text DEFAULT NULL COMMENT 'json array of error data as returned from accounts system',
  `accounts_needs_update` tinyint DEFAULT 0 COMMENT 'Include in next push to accounts',
  `connector_id` int unsigned DEFAULT 0 COMMENT 'ID of connector. Relevant to connect to more than one account of the same type',
  `is_error_resolved` tinyint DEFAULT 0 COMMENT 'Filter out if resolved',
  `plugin` varchar(32) COMMENT 'Name of plugin creating the account',
  PRIMARY KEY (`id`),
  INDEX `index_contribution_invoice`(contribution_id),
  UNIQUE INDEX `UI_account_system_id`(accounts_invoice_id, connector_id, plugin),
  UNIQUE INDEX `UI_invoice_id_plugin`(contribution_id, connector_id, plugin),
  CONSTRAINT FK_civicrm_account_invoice_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;
