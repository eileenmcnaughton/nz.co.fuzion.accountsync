<?php

namespace Civi\Api4;

/**
 * Trait fo actions to manage account sync entities.
 */
trait AccountSyncManageTrait {
  /**
   * @param bool $checkPermissions
   * @return Action\Contribution\Create
   */
  public static function queue($checkPermissions = TRUE) {
    return (new Action\AccountSync\Queue(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }
}