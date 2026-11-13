# Account Sync (nz.co.fuzion.accountsync)

Account Sync is the shared foundation that CiviCRM accounting integrations are built on. On its own it does not talk to any accounting system. Instead it:

- decides **which** CiviCRM contacts and contributions need to be sent to an accounting system, and queues them;
- stores the **link** between each CiviCRM record and its matching record in the accounting system, along with the last data received and any error;
- provides **screens** for finding and fixing sync errors, matching contacts and spotting contributions that were never queued;
- provides **APIs and hooks** that connector extensions use to do the actual sending and receiving.

You need a connector extension installed alongside it. The two maintained connectors are:

| Connector | Accounting system | Plugin name |
|---|---|---|
| [CiviXero](https://github.com/eileenmcnaughton/nz.co.fuzion.civixero) (`nz.co.fuzion.civixero`) | Xero | `xero` |
| [CiviQuickBooks](https://github.com/agileware/au.com.agileware.civiquickbooks) (`au.com.agileware.civiquickbooks`) | QuickBooks Online | `quickbooks` |

Both connectors declare Account Sync as a required extension, so CiviCRM will install it for you.

**Contents**

- [How it works](#how-it-works)
- [Features](#features)
- [Settings](#settings)
- [Screens and menu items](#screens-and-menu-items)
- [Recommended ongoing checks](#recommended-ongoing-checks)
- [Troubleshooting](#troubleshooting)
- [How connector extensions use Account Sync](#how-connector-extensions-use-account-sync)
- [Developer reference](#developer-reference)
- [Known limitations](#known-limitations)

## How it works

### The two tracking tables

Account Sync adds two database tables. Every sync decision revolves around them.

| Table | One row per | Key columns |
|---|---|---|
| `civicrm_account_contact` (API entity `AccountContact`) | CiviCRM contact, per connector | `contact_id`, `accounts_contact_id` |
| `civicrm_account_invoice` (API entity `AccountInvoice`) | CiviCRM contribution, per connector | `contribution_id`, `accounts_invoice_id` |

Both tables share these columns:

| Column | Meaning |
|---|---|
| `plugin` | Which connector owns the row, e.g. `xero` or `quickbooks`. |
| `connector_id` | Which account of that type. `0` for a normal single-account site. Only non-zero when the optional Connectors extension is used to link several accounts (see [Multiple accounts](#multiple-accounts-connectors)). |
| `accounts_needs_update` | **The queue flag.** When set, the connector will push this record on its next run. |
| `accounts_data` | The last data received from the accounting system (JSON, or a connector-specific value). |
| `error_data` | The last error, if any. The format depends on the connector. |
| `is_error_resolved` | Lets an error be hidden from the error lists without deleting it. |
| `last_sync_date` | Updated automatically every time the row changes. |
| `accounts_modified_date` | When the record was last changed in the accounting system. |

`civicrm_account_contact` also has:

- `accounts_display_name`, the name in the accounting system;
- `do_not_sync`, which permanently excludes the contact.

`civicrm_account_invoice` also has `accounts_status_id`, the invoice status in the accounting system, mapped to one of:

| ID | Name | Meaning |
|---|---|---|
| 0 | `unknown` | Not yet known |
| 1 | `completed` | Paid in the accounting system |
| 2 | `pending` | Draft, submitted, authorised or open |
| 3 | `cancelled` | Voided or deleted |

A row with an empty `accounts_contact_id` / `accounts_invoice_id` has been queued but **not yet created** in the accounting system.

### Queueing: how records get flagged for sync

Account Sync watches CiviCRM saves (using `hook_civicrm_post`). When a relevant record is created or changed, it creates or flags the matching tracking row with `accounts_needs_update = 1`. Which saves count is controlled by the [settings](#settings).

**Contacts**

- If a contact's contribution (or another "create" entity, see settings) is saved and the contact has no tracking row yet, a new row is created and queued.
- If a contact who **already** has a tracking row has their name, email, phone or address changed, the row is re-queued.
- Deleted (trashed) contacts are never queued.

**Contributions (invoices)**

A contribution is queued when it is created or edited, **unless any of these apply**:

- it is a test contribution;
- its total amount is zero;
- its status is not one of the selected *Push Contribution Status* values (by default Completed and Pending);
- its receive date is before the *Day zero* date;
- it was made through a payment processor on the *skip* list.

**Only meaningful changes re-queue a record.** CiviCRM often re-saves records without changing anything, for example when a scheduled job tidies data. Account Sync compares the record before and after the save and only re-queues when one of these fields actually changed:

| Record | Fields that trigger a re-queue |
|---|---|
| Contact | first name, last name, display name, organisation name, household name |
| Email, Phone | the value, primary flag, location type |
| Address | street, supplemental lines 1 to 3, city, postcode, state, country, primary flag, location type |
| Contribution | contact, total amount, status, receive date, financial type, transaction ID, currency, source |

If Account Sync can't tell whether something changed, it queues the record anyway.

**Account Sync only queues on save.** Contributions that existed before the extension was installed, or before a setting was changed, are not queued automatically. See [Syncing historical contributions](#syncing-historical-contributions).

### Pushing and pulling: done by the connector

The connector's scheduled jobs then do the actual work:

1. **Contact push** sends queued contacts to the accounting system. It records the remote ID in `accounts_contact_id` and clears the queue flag.
2. **Invoice push** sends queued contributions as invoices. It looks up the contact's `accounts_contact_id`, so the contact must be pushed first. It records the remote ID in `accounts_invoice_id`.
3. **Contact pull** brings in contacts from the accounting system. It updates `accounts_data` and links rows to CiviCRM contacts where a match can be made.
4. **Invoice pull** checks invoice status in the accounting system. It updates `accounts_status_id`, and depending on the connector, updates the CiviCRM contribution.

If a push fails, the connector writes the error to `error_data`. The record then appears in the [error lists](#screens-and-menu-items) until it is fixed and retried.

### Updating contributions from the accounting system

Account Sync provides an API action, `AccountInvoice.update_contribution`, that copies invoice status back into CiviCRM:

- `accounts_status_id = 1` (completed): for each tracked invoice marked *completed* whose contribution is still **Pending**, records a payment (`Payment.create`), which completes the contribution. If the connector supplies the paid date and amount (CiviXero does), they are used and duplicate payments are avoided. The *Send receipts for Contributions* setting controls whether a receipt is sent.
- `accounts_status_id = 3` (cancelled): sets the contribution to **Cancelled** for each tracked invoice marked *cancelled*.

CiviXero sets up two scheduled jobs that call this action. CiviQuickBooks does not use it. It updates contributions directly during its own Invoice Pull.

### Multiple accounts (Connectors)

If the optional [Connectors](https://github.com/eileenmcnaughton/nz.co.fuzion.connectors) extension is installed and connector records exist for Account Sync, each connector gets its own set of tracking rows (`connector_id`). If a connector is linked to a contact, only line items whose financial type's income account belongs to that contact are sent through it. This allows one CiviCRM site to feed several accounting organisations. Without Connectors, everything uses `connector_id = 0`.

## Features

- Automatic, change-aware queueing of contacts and contributions for sync.
- Per-record tracking of the remote ID, last remote data, remote status and last error.
- Configurable rules for what gets queued: trigger entities, contribution statuses, a "day zero" cut-off date and payment processors to skip.
- SearchKit error lists for contacts and invoices, with bulk **Retry sync**, **Mark as resolved** and **Do not sync** actions.
- A **Non-Queued Contributions** list for finding contributions that will never be sent.
- **Match Contacts** screens for linking contacts pulled from the accounting system to existing CiviCRM contacts.
- A **List of synced contacts** screen.
- An API action to complete or cancel CiviCRM contributions based on invoice status in the accounting system.
- Contact delete and merge protection: warns when deleting a synced contact or contribution, and keeps the link when merging.
- Support for several accounting connections on one site (with the Connectors extension).
- A System Status check for damaged tracking rows.
- Database logging support (both tables are logged when CiviCRM detailed logging is on).

## Settings

Account Sync has no settings page of its own. Each connector moves the Account Sync settings onto **its** settings page:

- CiviXero: **Contributions > Accounts Integration > Xero Settings** (`civicrm/admin/setting/xero`)
- CiviQuickBooks: **Contributions > Quickbooks Settings** (`civicrm/admin/setting/quickbooks`)

| Setting | Machine name | Default | What it does |
|---|---|---|---|
| Entities to trigger contact create | `account_sync_queue_contacts` | Contribution | Saving one of these creates a tracking row for a contact who does not have one yet. Options: Contact, Contribution, Recurring Contribution. Choosing **Contact** sends *every* contact you save, so most sites use only Contribution. |
| Entities to trigger contact update | `account_sync_queue_update_contacts` | Contact, Email, Address, Phone | Saving one of these re-queues a contact who **already** has a tracking row. |
| Entities to trigger invoice create | `account_sync_queue_create_invoice` | Contribution | Saving one of these queues the contribution as an invoice. |
| Payment processors to skip | `account_sync_skip_inv_by_pymt_processor` | none | Contributions made through these processors are not queued. Useful when the processor already sends its own transactions to your accounts. |
| Day zero | `account_sync_contribution_day_zero` | 1990-01-01 | Contributions received before this date are never queued. Set it to your go-live date. |
| Push Contribution Status | `account_sync_push_contribution_status` | Completed, Pending | Only contributions in these statuses are queued. |
| Send receipts for Contributions | `account_sync_send_receipt` | No override | When `AccountInvoice.update_contribution` completes a contribution: *Send*, *Do not send*, or leave CiviCRM's normal behaviour. |

Changing a setting does **not** re-evaluate records that were already queued or skipped. CiviXero re-checks status, day zero and payment processor at push time. CiviQuickBooks does not.

## Screens and menu items

All screens are under **Contributions > Accounts Integration** and require the *administer CiviCRM system* or *administer CiviCRM data* permission.

| Menu item | Path | Use it to |
|---|---|---|
| Errors > Invoice Errors | `civicrm/accounting/errors/invoices` | See contributions whose last push or pull failed. |
| Errors > Contact Errors | `civicrm/accounting/errors/contacts` | See contacts whose last push failed. |
| Match Contacts | `civicrm/admin/accountsync/contactmatch` | Link accounting-system contacts that have no CiviCRM contact, or that have not yet been created remotely. |
| Match Contacts (legacy) | `civicrm/a/#/accounts/contact/sync` | Older suggestion-based matching screen. Defaults to the `xero` plugin. Add `/quickbooks` to the URL for QuickBooks. |
| List of synced contacts | `civicrm/admin/accountsync/contactlist` | Browse every contact tracking row and its status. |
| Non-Queued Contributions | `civicrm/admin/accountsync/contributionlist` | Find Completed or Pending, non-test contributions that have **no** invoice tracking row and so will never be sent. |

The error lists only show rows where `error_data` is set **and** `is_error_resolved` is not set. Select rows and use the **Actions** menu:

| Action | Effect |
|---|---|
| **Retry sync** | Clears the error, marks it resolved and re-queues the record for the next push. Use after fixing the cause. |
| **Mark as resolved** | Hides the error without re-queuing. Use when the problem was fixed in the accounting system by hand or doesn't matter. |
| **Do not sync** | Excludes the record from all future pushes and hides the error. Use for test data or records that should never be sent. |

Connectors may add their own views too. For example, both CiviXero and CiviQuickBooks add sync status and error blocks to the Contact Summary, and add a System Status message when errors exist or authorisation has expired.

## Recommended ongoing checks

**Daily or after each run of the sync jobs**

1. **Check the error lists.** Open *Invoice Errors* and *Contact Errors*. Fix and retry each error, or deliberately resolve it. An unresolved error usually means that record is **not** being retried.
2. **Check System Status** (*Administer > Administration Console > System Status*). Look for Account Sync and connector messages, especially expired authorisation.
3. **Check the scheduled job log** for your connector's push and pull jobs (*Administer > System Settings > Scheduled Jobs*, then *View Job Log*). Confirm they ran and look for failures or rate-limit messages. Also confirm cron is running.

**Weekly**

4. **Check Non-Queued Contributions.** Anything listed here will not reach your accounts. Expected entries include contributions before day zero or from skipped payment processors. Investigate anything else, for example contributions imported or created in a way that did not trigger queueing.
5. **Check Match Contacts** for contacts pulled from the accounting system that are not linked to a CiviCRM contact. Unlinked contacts are a common cause of duplicates in the accounting system.

**Monthly or at period end**

6. **Reconcile totals.** Compare contribution totals in CiviCRM for the period (excluding test contributions, and from day zero onward) with the matching invoices in your accounting system. Differences usually trace back to errors, non-queued contributions, or edits made on only one side.
7. **Review financial account codes.** Check that every financial type in use has an income account whose *Acctg Code* matches an account or item in the accounting system. New financial types are the most common cause of invoice errors.
8. **Review the settings** after any change to payment processors, contribution statuses or financial types.

**After a CiviCRM or extension upgrade**

9. Clear caches and check System Status. A message about **records with a NULL connector_id** means some tracking rows need `connector_id` set to `0` (or to the connector ID if you use Connectors).

## Troubleshooting

### "My contribution has not appeared in the accounting system"

Work through these in order:

1. **Is it queued?** Look the contribution up in *Non-Queued Contributions*. If it is listed, it has no tracking row. Check it against the queueing rules: test, zero amount, status not in *Push Contribution Status*, received before *Day zero*, or a skipped payment processor. If it should have been queued, make a small real edit (for example to the source field) and save it, or use your connector's bulk queue feature.
2. **Is it in error?** Check *Invoice Errors*. Fix the cause (see below) and use **Retry sync**.
3. **Has the contact been pushed?** An invoice can't be created until its contact exists in the accounting system. Check *Contact Errors* and the contact's sync status on the Contact Summary.
4. **Have the jobs run?** Check that the connector's push jobs are enabled and that cron is running. The connectors install their jobs **disabled**.
5. **Is the connector blocked?** Both connectors stop calling the accounting system for a period after a rate-limit response, and stop entirely if authorisation has expired. Check System Status and the job log.

### Common errors

| Symptom | Likely cause | Fix |
|---|---|---|
| "No Income Account code configured for financial type N" | The financial type has no income account with an *Acctg Code*. | Set the accounting code under *Administer > CiviContribute > Financial Accounts*, then Retry sync. |
| Item or account code not found in the accounting system | The *Acctg Code* doesn't match anything in the accounting system. | Correct the code on one side, then Retry sync. |
| Contact has no accounting system ID | The invoice was pushed before its contact. | Wait for the contact push, or fix the contact's error first. |
| Duplicate or "already matched" contact errors | Two CiviCRM contacts map to one accounting contact, or the reverse. | Merge the CiviCRM duplicates, or relink using *Match Contacts*. Then Retry sync. |
| Contribution completed or cancelled in the accounting system but not in CiviCRM | The pull or update job hasn't run, or failed. | Check the job log. For CiviXero, check the *Complete/Cancel Contributions From Accounts* jobs are enabled. |
| Error retried but still listed | The cause wasn't fixed, or the connector re-raised the same error. | Read the full error text in the error list, or on the Contact Summary block, before retrying again. |
| System Status: NULL connector_id | Rows created by older code or manual data changes. | Set `connector_id` to `0` on those rows (or the right connector ID). |

### Deleting and merging

- **Deleting a contact or contribution that was never synced** removes its tracking row automatically.
- **Trashing a contact that was synced** shows a warning. Restore the contact, because the accounting system still refers to it.
- **Permanently deleting a synced contact** removes the tracking row and shows a warning. The record in the accounting system is not touched, so tidy it up there.
- **Deleting a synced contribution** shows a warning. Void or delete the invoice in the accounting system yourself.
- **Merging contacts** keeps the tracking row of the contact you keep. If only the deleted contact was synced, its link moves to the kept contact. If **both** were synced, the deleted contact's link is not carried over. Check the accounting system for a duplicate.

### Asking for help

When reporting a problem, include:

- the CiviCRM, Account Sync and connector versions;
- the full error text from the error list;
- the tracking row's `accounts_needs_update`, `error_data` and `is_error_resolved` values (you can read these with API4 Explorer, entity `AccountInvoice` or `AccountContact`);
- the relevant scheduled job log entry.

## How connector extensions use Account Sync

Both CiviXero and CiviQuickBooks follow the same pattern:

1. **Register a plugin name** through `hook_civicrm_accountsync_plugins`, so Account Sync creates tracking rows for that plugin.
2. **Let Account Sync queue.** Neither connector decides what to queue during normal operation. They read rows with `accounts_needs_update = 1`.
3. **Push** the queued rows in scheduled jobs. Each connector calls `accountPushAlterMapped` before sending each record, then saves the remote ID, data and error back to the tracking row and clears the queue flag.
4. **Pull** remote changes in scheduled jobs. Each connector calls `accountPullPreSave` before saving each remote record to a tracking row.
5. **Implement `mapAccountsData`** to translate stored remote contact data into CiviCRM field names, used by the matching screens.
6. **Show Account Sync settings on their own settings page**, using `hook_civicrm_alterSettingsMetaData` on settings in the `accountsync` group.
7. **Link to Account Sync's error lists** from their own System Status checks and Contact Summary blocks.

Differences between the two connectors:

| | CiviXero | CiviQuickBooks |
|---|---|---|
| Plugin name | `xero` | `quickbooks` |
| Multiple accounts (Connectors) | Supported | Single account only (`connector_id = 0`) |
| Builds invoices from | `AccountInvoice.getderived` | Its own line-item code |
| Updates CiviCRM contributions from remote status | Through `AccountInvoice.update_contribution` jobs | Directly in its Invoice Pull |
| Can create CiviCRM contributions from remote invoices | Optional (`create_contributions_in_civicrm` on Invoice Pull) | No |
| Bulk queue of historical contributions | No | Yes ("Queue all contributions since Day zero?") |
| Re-checks queue rules at push time | Yes | No |
| Uses `is_error_resolved` | Yes | No (uses its own failure count and `do_not_sync`) |

Each connector has its own documentation covering authorisation, mapping and job setup:

- CiviXero: <https://github.com/eileenmcnaughton/nz.co.fuzion.civixero>
- CiviQuickBooks: <https://github.com/agileware/au.com.agileware.civiquickbooks>

### Syncing historical contributions

Because Account Sync only queues on save, contributions that existed before go-live are not sent. Options:

- **CiviQuickBooks:** tick *Queue all contributions since Day zero?* on its settings page. It applies the same rules as Account Sync and queues the matching contributions in batches.
- **Any connector:** create tracking rows with the API, for example `AccountInvoice.create` with `plugin`, `connector_id = 0`, `contribution_id` and `accounts_needs_update = 1`. Do the same with `AccountContact` for the contacts.

Before a bulk sync, set *Day zero* carefully, map all accounting codes, and test with a small date range first. Afterwards, check *Non-Queued Contributions*.

## Developer reference

### APIs

**API4:** `AccountContact` and `AccountInvoice` support the standard actions (`get`, `create`, `update`, `save`, `delete`). They are also available in SearchKit.

**API3:**

| Entity.action | Purpose |
|---|---|
| `AccountContact.create / get / delete` | Basic CRUD. `get` decodes `accounts_data` and calls `mapAccountsData`. |
| `AccountContact.link` | Link a tracking row to a contact. Removes any unlinked row for that contact first, and fails if the contact is already linked. |
| `AccountContact.getsuggestions` | Suggest a match for unlinked rows: an existing contact with the same display name, *create* or *do not sync*. |
| `AccountContact.savesuggestions` | Apply all `link_contact` suggestions. |
| `AccountInvoice.create / get / delete` | Basic CRUD. |
| `AccountInvoice.getderived` | Return a contribution with its line items, including event participant line items. Each line item gets its accounting code and accounts contact, and the payment instrument's accounting code is added. Pass `id` (the contribution ID). |
| `AccountInvoice.update_contribution` | Pass `accounts_status_id=1` to complete Pending contributions whose invoice is paid, or `accounts_status_id=3` to cancel contributions whose invoice is cancelled. |

For `update_contribution` with completed status: if a connector provides an API4 action `AccountInvoice.getAccountsData<Plugin>` (CiviXero provides `getAccountsDataXero`), it is used to get the real paid date, amount and status, and to avoid duplicate payments. Without that action, the contribution is completed using its own receive date and total.

### Hooks

| Hook | Called by | Purpose |
|---|---|---|
| `hook_civicrm_accountsync_plugins(&$plugins)` | Account Sync | Add your plugin name to the array. Tracking rows are only created for registered plugins. |
| `hook_civicrm_accountPushAlterMapped($entity, &$data, &$save, &$params)` | Connectors, before pushing | Change the mapped data (for example, account codes or tracking categories), or set `$save = FALSE` to skip the record. `$entity` is `contact`, `invoice` or (CiviXero) `bank_transaction`. |
| `hook_civicrm_accountPullPreSave($entity, &$data, &$save, &$params)` | Connectors, before saving pulled data | Change remote data before it is stored, or set `$save = FALSE` to skip it. Unlike `hook_civicrm_pre`, skipping is supported. |
| `hook_civicrm_mapAccountsData(&$accountsData, $entity, $plugin)` | `AccountContact.get` | Add a `civicrm_formatted` key containing the remote data in CiviCRM API field names. |

Example: skip pulling a record.

```php
function myext_civicrm_accountPullPreSave($entity, &$data, &$save, &$params) {
  if ($entity === 'contact' && my_should_ignore($data)) {
    $save = FALSE;
  }
}
```

Example: override the account code for one financial type.

```php
function myext_civicrm_accountPushAlterMapped($entity, &$data, &$save, &$params) {
  // Inspect $data (the CiviCRM record) and alter $params (the mapped payload).
}
```

The shape of `$data` and `$params` differs between connectors and between versions. Check the connector's code before relying on it.

### Helper functions available to connectors

These are internal functions in `accountsync.php`, but connectors use them so they behave the same way as Account Sync:

- `_accountsync_get_connectors()`
- `_accountsync_create_account_contact($contactID, $createNew, $connectorID)`
- `_accountsync_create_account_invoice($contributionID, $connectorID)`
- `_accountsync_get_contact_create_entities($connectorID)`, `_accountsync_get_invoice_create_entities($connectorID)`, `_accountsync_get_skip_invoice_create_entities($connectorID)`

### Suppressing queueing while a connector writes to CiviCRM

When a connector creates a contribution from a remote invoice, it should stop Account Sync from queueing that contribution for push. To do that, set:

```php
\Civi::$statics['data.accountsync.createcontribution']['createnew'] = FALSE;
```

and unset it afterwards. While it is `FALSE`, Account Sync's pre and post hooks do nothing.

### Change detection

The fields that count as a "meaningful change" are listed in `_accountsync_get_sync_relevant_fields()` in `accountsync.php`. If a connector starts pushing a field that is not on that list, changes to that field will not re-queue the record. Add the field to the list.

### Tests

PHPUnit tests are in `tests/phpunit` and need a CiviCRM test build (`cv`). Run:

```bash
phpunit9
```

## Known limitations

- Queueing happens only when records are saved. Bulk SQL imports and direct database changes are not detected.
- Account Sync never updates CiviCRM contact details from the accounting system.
- Changing a contribution after its invoice has been approved or locked in the accounting system may not flow through. This depends on the connector and the accounting system's rules.
- Overpayments and edits that make a contribution total differ from its line items are not reconciled automatically.
- Cancelling a contribution through `update_contribution` does not cancel related event registrations or memberships.
- Merging two contacts that are **both** synced keeps only the kept contact's link.
- The API3 entities `AccountLineItems` and `AccountPayment` are left over from earlier designs and have no backing table. Do not use them.

## Licence

AGPL-3.0. See [LICENSE.txt](LICENSE.txt).
