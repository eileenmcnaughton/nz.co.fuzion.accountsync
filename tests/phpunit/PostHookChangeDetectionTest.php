<?php

use Civi\Test\Api3TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\ContactTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Characterization tests for the "did anything actually change" guard in
 * accountsync_civicrm_post()/accountsync_civicrm_pre().
 *
 * Background: CiviCRM's hook_civicrm_post fires whenever a Contact/Email/
 * Phone/Address record is saved, even if the save didn't change any field
 * value (e.g. a routine resave by an unrelated scheduled job, such as
 * address geocoding retries or CiviMail bounce processing touching an
 * Email record). Without a real-change check, accountsync would flag the
 * linked contact's AccountContact row (accounts_needs_update = 1) on every
 * such resave, regardless of whether there's anything new to push to the
 * accounts package - which is what caused a stable set of already-synced
 * contacts to be silently re-queued for push every day with no user-visible
 * edit ever having happened.
 *
 * @group headless
 */
class PostHookChangeDetectionTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Api3TestTrait;
  use ContactTestTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * hook_civicrm_accountsync_plugins - registers 'xero' as the only enabled
   * plugin, matching what nz.co.fuzion.civixero would register in
   * production. Without at least one enabled plugin,
   * _accountsync_create_account_contact() is a no-op (its per-plugin loop
   * never executes), which would make every test in this file pass for the
   * wrong reason.
   */
  public function hook_civicrm_accountsync_plugins(&$plugins) {
    $plugins[] = 'xero';
  }

  /**
   * Create a contact plus an AccountContact row representing a contact
   * that has already been synced and is not currently queued for update.
   *
   * @param int $contactID
   *
   * @return int
   *   The AccountContact id.
   */
  private function createSyncedAccountContact(int $contactID): int {
    $accountContact = $this->callAPISuccess('AccountContact', 'create', [
      'contact_id' => $contactID,
      'plugin' => 'xero',
      'connector_id' => 0,
      'accounts_contact_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
      'accounts_needs_update' => 0,
    ]);
    return (int) $accountContact['id'];
  }

  private function getAccountsNeedsUpdate(int $accountContactID): int {
    $accountContact = $this->callAPISuccessGetSingle('AccountContact', ['id' => $accountContactID]);
    return (int) $accountContact['accounts_needs_update'];
  }

  /**
   * A no-op resave of an Email (identical field values, as a background
   * job doing an unconditional resave would produce) must NOT re-flag an
   * already-synced contact for update.
   */
  public function testEmailNoOpResaveDoesNotFlagContactForUpdate(): void {
    $contactID = $this->individualCreate();
    $email = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => 'unchanged@example.org',
      'location_type_id' => 1,
      'is_primary' => 1,
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    // Resave with exactly the same values - e.g. a scheduled job that
    // touches the row (bounce processing, etc.) without actually changing
    // anything relevant to accounts sync.
    $this->callAPISuccess('Email', 'create', [
      'id' => $email['id'],
      'contact_id' => $contactID,
      'email' => 'unchanged@example.org',
      'location_type_id' => 1,
      'is_primary' => 1,
    ]);

    $this->assertEquals(0, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * A real change to an Email's address must still flag the contact for
   * update, exactly as before this fix.
   */
  public function testEmailRealChangeFlagsContactForUpdate(): void {
    $contactID = $this->individualCreate();
    $email = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => 'old@example.org',
      'location_type_id' => 1,
      'is_primary' => 1,
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    $this->callAPISuccess('Email', 'create', [
      'id' => $email['id'],
      'contact_id' => $contactID,
      'email' => 'new@example.org',
      'location_type_id' => 1,
      'is_primary' => 1,
    ]);

    $this->assertEquals(1, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * A no-op resave of an Address must NOT re-flag an already-synced
   * contact for update. This is the specific case that a recurring
   * "retry failed geocodes" job would trigger every run for any address
   * that can never successfully geocode.
   */
  public function testAddressNoOpResaveDoesNotFlagContactForUpdate(): void {
    $contactID = $this->individualCreate();
    $address = $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contactID,
      'location_type_id' => 1,
      'is_primary' => 1,
      'street_address' => '123 Test Street',
      'city' => 'Perth',
      'postal_code' => '6000',
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    $this->callAPISuccess('Address', 'create', [
      'id' => $address['id'],
      'contact_id' => $contactID,
      'location_type_id' => 1,
      'is_primary' => 1,
      'street_address' => '123 Test Street',
      'city' => 'Perth',
      'postal_code' => '6000',
    ]);

    $this->assertEquals(0, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * A real change to an Address must still flag the contact for update,
   * exactly as before this fix.
   */
  public function testAddressRealChangeFlagsContactForUpdate(): void {
    $contactID = $this->individualCreate();
    $address = $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contactID,
      'location_type_id' => 1,
      'is_primary' => 1,
      'street_address' => '123 Test Street',
      'city' => 'Perth',
      'postal_code' => '6000',
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    $this->callAPISuccess('Address', 'create', [
      'id' => $address['id'],
      'contact_id' => $contactID,
      'location_type_id' => 1,
      'is_primary' => 1,
      'street_address' => '456 New Street',
      'city' => 'Perth',
      'postal_code' => '6000',
    ]);

    $this->assertEquals(1, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * A no-op resave of the Contact itself (e.g. re-saving with identical
   * name fields) must NOT re-flag an already-synced contact for update.
   */
  public function testContactNoOpResaveDoesNotFlagContactForUpdate(): void {
    $contactID = $this->individualCreate([
      'first_name' => 'Jane',
      'last_name' => 'Citizen',
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    $this->callAPISuccess('Contact', 'create', [
      'id' => $contactID,
      'contact_type' => 'Individual',
      'first_name' => 'Jane',
      'last_name' => 'Citizen',
    ]);

    $this->assertEquals(0, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * A real change to the Contact's name must still flag it for update,
   * exactly as before this fix.
   */
  public function testContactRealChangeFlagsContactForUpdate(): void {
    $contactID = $this->individualCreate([
      'first_name' => 'Jane',
      'last_name' => 'Citizen',
    ]);
    $accountContactID = $this->createSyncedAccountContact($contactID);

    $this->callAPISuccess('Contact', 'create', [
      'id' => $contactID,
      'contact_type' => 'Individual',
      'first_name' => 'Janet',
      'last_name' => 'Citizen',
    ]);

    $this->assertEquals(1, $this->getAccountsNeedsUpdate($accountContactID));
  }

  /**
   * Creating a brand new Contact (an 'edit' op does not apply - there is
   * no "before" state to compare against) must still flag it for update,
   * same as before this fix. This guards against the "did it change"
   * check accidentally suppressing legitimate creates.
   */
  public function testNewContactCreationStillFlagsForUpdate(): void {
    $contactID = $this->individualCreate();

    $accountContact = $this->callAPISuccessGetSingle('AccountContact', [
      'contact_id' => $contactID,
      'plugin' => 'xero',
    ]);

    $this->assertEquals(1, $accountContact['accounts_needs_update']);
  }

  /**
   * _accountsync_entity_has_relevant_change() must fail "open" (assume
   * changed) when no pre-save snapshot was captured for the given entity
   * id - e.g. because hook_civicrm_pre never ran for it in this request.
   * This is what keeps the optimisation safe: it can only ever suppress
   * an unnecessary flag, never miss a real one.
   */
  public function testChangeDetectionFailsSafeWithNoCapturedSnapshot(): void {
    $this->assertTrue(
      _accountsync_entity_has_relevant_change('edit', 'Email', 999999, (object) ['email' => 'x@example.org'])
    );
  }

  /**
   * Entities we don't have a known sync-relevant field list for (e.g.
   * Contribution) are always treated as changed - the "did it change"
   * optimisation only applies to Contact/Email/Phone/Address.
   */
  public function testChangeDetectionAlwaysTrueForUnknownEntityType(): void {
    $this->assertTrue(
      _accountsync_entity_has_relevant_change('edit', 'Contribution', 999999, (object) ['total_amount' => 100])
    );
  }

}
