This extension supports the CiviXero extension by doing the non Xero specific parts. It is a dependency although CiviCRM has no dependency management

IT PROVIDES
1) tables for storing event sync info - in particular civicrm_account_contact & civicrm_invoice_contact

2) apis for storing data in these tables

3) hooks civicrm_accountPullPreSave & civicrm_accountPushAlterMapped
 note that the civicrm_accountPullPreSave differs from a standard pre hook in that setting save to FALSE will cause the record to be skipped ie.

 hook_civicrm_accountPullPreSave($entity, &$data, &$save, &$params) {
   if (not_cool($entity)) {
     $save = FALSE;
   }
 }

 the accountPushAlterMapped hook also allows you to stop something from being pushed through although it's primary use is to change the mapping (e.g alter account codes based on client specific logic

 hook_accountPushAlterMapped($entity, &$data, &$save, &$params) {}

4) api invoice.getderived. This turned out to be a fairly important api as the line items for a given contact id are not easily retrieved. If the contribution is for a participant_payment then the participant entity rather than the contribution entity links to the civicrm_line_item table. I may not have used the best process to build this 'invoice' entity but it's a much better starting place for accounts interaction. It also resolves all accounting codes with is relevant for accounts interaction
5) api to update civicrm contributions based on related invoice status

6) api for cancelling payments based on civicrm_account_invoice - ie taking those items where the status in accounts is cancelled & not in CiviCRM. GOTCHA - cancelling an invoice via the API doesn't cancel the event registraion - feature ? bug?

TODO


1) Think more about how people will create matches for accounts data to get them in sync. Currently this is do-able via hooks & so far my experience has been that the approach has been quite customer specific and involves a good deal of manual effort. It may be beyond the scope of this extension.

2) Currently there is nothing in place to update contacts from accounts package data - I think that is out of the scope of this extension

3) in my previous Xero implementation overpaid invoices would be adjusted in CiviCRM. I haven't figured out how this would look in the brave new world of CiviAccounts. I'm not currently storing the amount except in the extension specific field. Another unhandled situation is when the total value of the contribution is edited not to match the total of the price set

4) there is a merge hook - but it only works when one contact has an accountsync contact & the other doesn't.


THOUGHTS on whether to enforce the contribution_id & contact_id in these tables
1) I should either be relaxed & store the invoice ids / contact ids from the accounts data regardless or
2) I should use the FKs to restrict the data in the 'parent' table from being deleted. In theory contributions
can't be deleted anyway & in theory neither can contacts who have made contributions.
