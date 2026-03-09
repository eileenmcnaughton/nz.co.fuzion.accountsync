<?php

use Civi\Test\Api3TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\ContactTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class CreateAccountInvoiceTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Api3TestTrait;
  use ContactTestTrait;

  /**
   * Should AccountInvoice writes be sabotaged? Set per test.
   *
   * @var bool
   */
  private $breakAccountInvoiceWrites = FALSE;

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Without at least one enabled plugin _accountsync_create_account_invoice()
   * is a no-op (its per-plugin loop never executes), which would make these
   * tests pass for the wrong reason.
   */
  public function hook_civicrm_accountsync_plugins(&$plugins) {
    $plugins[] = 'xero';
  }

  /**
   * Simulate the AccountInvoice write failing.
   */
  public function hook_civicrm_pre($op, $objectName, $id, &$params) {
    if ($this->breakAccountInvoiceWrites && $objectName === 'AccountInvoice') {
      throw new CRM_Core_Exception('Simulated AccountInvoice write failure');
    }
  }

  private function createEligibleContribution(int $contactID): array {
    return $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactID,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 'Completed',
      'total_amount' => 100,
      'receive_date' => '2024-01-01 00:00:00',
    ]);
  }

  /**
   * A new eligible Contribution gets an AccountInvoice flagged for sync.
   */
  public function testContributionQueuesInvoiceForSync(): void {
    $contactID = $this->individualCreate();
    $contribution = $this->createEligibleContribution($contactID);

    $accountInvoice = $this->callAPISuccessGetSingle('AccountInvoice', [
      'contribution_id' => $contribution['id'],
      'plugin' => 'xero',
    ]);

    $this->assertEquals(1, $accountInvoice['accounts_needs_update']);
  }

  /**
   * A failure flagging the AccountInvoice must not take the contribution down
   * with it - the payment the user made is what matters, the sync flag can be
   * recovered later.
   */
  public function testAccountInvoiceWriteFailureDoesNotLoseTheContribution(): void {
    $contactID = $this->individualCreate();
    $this->breakAccountInvoiceWrites = TRUE;

    $contribution = $this->createEligibleContribution($contactID);

    $this->breakAccountInvoiceWrites = FALSE;
    $persisted = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals(100, $persisted['total_amount']);
    $this->callAPISuccess('AccountInvoice', 'getcount', [
      'contribution_id' => $contribution['id'],
    ], 0);
  }

}
