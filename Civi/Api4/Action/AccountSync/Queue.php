<?php

use Civi\Api4\Generic\Result;

/**
 * Queues an item for account updating - by resetting needs_update.
 */
class Queue extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Address string to convert to lat/long
   *
   * @var string
   *
   * @required
   */
  protected $address;

  public function _run(Result $result) {

  }

}
