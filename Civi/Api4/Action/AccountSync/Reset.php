<?php

use Civi\Api4\Generic\Result;

/**
 * Resets error data on an account entity.
 */
class Reset extends \Civi\Api4\Generic\AbstractAction {

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