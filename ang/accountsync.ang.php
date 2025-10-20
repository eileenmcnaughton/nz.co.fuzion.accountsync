<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' =>
    [
      0 => 'ang/accountsync.js',
      1 => 'ang/accountsync/*.js',
      2 => 'ang/accountsync/*/*.js',
    ],
  'css' =>
    [
      0 => 'ang/accountsync.css',
    ],
  'partials' =>
    [
      0 => 'ang/accountsync',
    ],
];
