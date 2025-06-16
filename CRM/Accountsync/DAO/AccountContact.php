<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $contact_id
 * @property string $accounts_contact_id
 * @property string $accounts_display_name
 * @property string $last_sync_date
 * @property string $accounts_modified_date
 * @property string $accounts_data
 * @property string $error_data
 * @property bool|string $accounts_needs_update
 * @property string $connector_id
 * @property string $plugin
 * @property bool|string $do_not_sync
 * @property bool|string $is_error_resolved
 */
class CRM_Accountsync_DAO_AccountContact extends CRM_Accountsync_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_account_contact';

}
