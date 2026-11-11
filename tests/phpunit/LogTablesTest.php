<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class LogTablesTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testLastSyncDateIsNotCompared(): void {
    $spec = (new CRM_Logging_Schema())->getLogTableSpec();
    foreach (['civicrm_account_contact', 'civicrm_account_invoice'] as $table) {
      $this->assertContains('last_sync_date', $spec[$table]['exceptions'] ?? [], $table);
    }
  }

}
