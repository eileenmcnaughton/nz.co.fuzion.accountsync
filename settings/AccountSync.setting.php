<?php

$results = civicrm_api3('PaymentProcessor', 'get', array(
  'sequential' => 0,
  'is_test' => 0,
  'return' => "id,name",
));

$processors = array();
foreach ($results['values'] as $processor => $details) {
  $processors[$processor] = $details['name'];
}

return array(
  'account_sync_queue_contacts' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_queue_contacts',
    'type' => 'Array',
    'add' => '4.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Entity updates to trigger queuing for contact creation',
    'title' =>  'Entities to trigger contact Create in the Remote System',
    'help_text' => 'When these entities are created the contact will be queued for sync',
    'default' => array('Contribution'),
    'html_type' => 'advmultiselect',
    'quick_form_type' => 'Element',
    'html_attributes' => array(
      'Contact' => 'Contact',
      'Contribution' => 'Contribution',
      'ContributionRecur' => 'Recurring Contribution',
    )
  ),
  'account_sync_queue_update_contacts' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_queue_update_contacts',
    'type' => 'Array',
    'add' => '4.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Trigger contact update when entity edited or created',
    'title' =>  'Entities to trigger contact update',
    'help_text' => 'When these entities are created the contact will be queued for update',
    'html_type' => 'advmultiselect',
    'default' => array('Contact', 'Email', 'Address', 'Phone'),
    'quick_form_type' => 'Element',
    'html_attributes' => array(
      'Contact' => 'Contact',
      'Contribution' => 'Contribution',
      'ContributionRecur' => 'Recurring Contribution',
      'Address' => 'Address',
      'Email' => 'Email',
      'Phone' => 'Phone',
    )
  ),
  'account_sync_queue_create_invoice' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_queue_create_invoice',
    'type' => 'Array',
    'add' => '4.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Trigger Invoice Creation when entity created',
    'title' =>  'Entities to trigger invoice create',
    'help_text' => 'When these entities are created an invoice will be queued for create',
    'html_type' => 'advmultiselect',
    'default' => array('Contribution'),
    'quick_form_type' => 'Element',
    'html_attributes' => array(
      'Contribution' => 'Contribution',
    )
  ),
  'account_sync_skip_inv_by_pymt_processor' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_skip_inv_by_pymt_processor',
    'type' => 'Array',
    'add' => '4.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Skip trigger of Invoice Creation when entities use this payment processor',
    'title' =>  'Payment processors to trigger skip of invoice create',
    'help_text' => 'When entities that use these payment processors are created an invoice will not be queued for create',
    'html_type' => 'advmultiselect',
    'default' => array(''),
    'quick_form_type' => 'Element',
    'html_attributes' => $processors,
  ),
  'account_sync_contribution_day_zero' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_contribution_day_zero',
    'type' => 'Date',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Do not include contributions prior to this date',
    'title' =>  'Day zero for contributions (eg. 2014-12-25 for Christmas 2014)',
    'help_text' => 'Earlier contributions are never synced.',
    'html_type' => 'Date',
    'default' => '1990-01-01',
    'quick_form_type' => 'Date',
    'html_attributes' => array(
      'formatType' => 'activityDateTime',
    )
  ),
  'account_sync_send_receipt' => array(
    'group_name' => 'Account Sync',
    'group' => 'accountsync',
    'name' => 'account_sync_send_receipt',
    'type' => 'Array',
    'add' => '4.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => array('no_override'),
    'title' => 'Send receipts for Contributions',
    'description' => '',
    'help_text' => 'Set \'Send receipt?\' option for all Contributions synced.',
    'html_type' => 'Select',
    'quick_form_type' => 'Element',
    'html_attributes' => array(
      'no_override' => 'No override',
      'send' => 'Send',
      'do_not_send' => 'Do not send',
    ),
  ),
 );
