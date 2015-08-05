(function(angular, $, _) {

  angular.module('accountsync').config(function($routeProvider) {
      $routeProvider.when('/accounts/contact/sync', {
        controller: 'AccountsyncEditCtrl',
        templateUrl: '~/accountsync/EditCtrl.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          suggestions: function(crmApi) {
            return crmApi('AccountContact', 'getsuggestions', {
              plugin: 'xero',
              do_not_sync: 0,
              contact_id: {'IS NULL' : 1},
              'sequential' : 1,
              'options' : {'limit' : 10}
            });
          },
          totalCount: function(crmApi) {
            return crmApi('AccountContact', 'getcount', {
              plugin: 'xero',
              do_not_sync: 0,
              contact_id: {'IS NULL' : 1}
            });
          }
        }
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   accountContacts -- The current account contacts, defined above in config().
  angular.module('accountsync').controller('AccountsyncEditCtrl', function($scope, crmApi, crmStatus, crmUiHelp, suggestions, totalCount) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('accountsync');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/accountsync/EditCtrl'}); // See: templates/CRM/accountsync/EditCtrl.hlp

    // We have accountContact available in JS. We also want to reference it in HTML.
    $scope.accountContacts = suggestions.values;
    $scope.totalCount = totalCount.result;

      $scope.save = function save(accountContact) {
        accountContact['accounts_data']['civicrm_formatted']['contact_type'] = 'Individual';
        switch (accountContact['suggestion']) {
          case 'do_not_sync':
            var success = crmStatus(
                {start: ts('Saving...'), success: ts('Saved')},
                crmApi('AccountContact', 'create', {
                  id: accountContact.id,
                  do_not_sync: 1,
                  contact_id: accountContact.contact_id,
                  plugin: 'xero'
                }).then(function(apiResult) {
                  $scope.removeItem($scope.accountContacts, accountContact);
                  $scope.totalCount--;
                })
            );
            break;

          case 'create_individual':
            if (!accountContact['accounts_data']['civicrm_formatted']['first_name']
            &! accountContact['accounts_data']['civicrm_formatted']['last_name']
            ) {
              var split = accountContact['accounts_data']['civicrm_formatted']['display_name'].split(' ');
              accountContact['accounts_data']['civicrm_formatted']['first_name'] = split.shift();
              accountContact['accounts_data']['civicrm_formatted']['last_name'] = split.pop();
            }
          case 'create_organization':

            var contactCreateParams = accountContact['accounts_data']['civicrm_formatted'];
            var contactTypeString = accountContact['suggestion'].split('_');
            contactCreateParams['contact_type'] = contactTypeString[1];
            if (contactCreateParams['contact_type'] == 'organization') {
              contactCreateParams['organization_name'] = contactCreateParams['display_name'];
            }
            contactCreateParams['api.AccountContact.create'] = {'id' : accountContact['id']};
            var success = crmStatus(
              {start: ts('Saving...'), success: ts('Saved')},
                crmApi('Contact', 'create', contactCreateParams)
            ).then(function(apiResult) {
              $scope.totalCount--;
              $scope.removeItem($scope.accountContacts, accountContact);
            },
            function(error) {
              console.log(error);
                }
            );
            break;

          case 'link_contact':
            var success = crmStatus(
              {start: ts('Saving...'), success: ts('Saved')},
              crmApi('AccountContact', 'create', {'id' : accountContact['id'], 'contact_id' : accountContact['suggested_contact_id']})
            )
            .then(function(apiResult) {
              $scope.totalCount--;
              $scope.removeItem($scope.accountContacts, accountContact);
            },
            function(apiResult) {
              accountContact['suggestion'] = 'do_not_sync';
              accountContact['is_error'] = true;
          })
                ;
            break;
        }

      var nextContact = crmApi('AccountContact', 'getsuggestions', {
          'id' : {'>' : $scope.accountContacts[$scope.accountContacts.length -1]['id']},
          'plugin' : 'xero',
          'options' : {'limit' : 1},
          'sequential' : 1
        }
      ).then(function(apiResult) {
        if (apiResult['values'].length > 0) {
          $scope.accountContacts.push(apiResult['values'].shift());
        }
      });
      return success;

    };

    $scope.removeItem = function(array, item) {
      var idx = _.indexOf(array, item);
      if (idx != -1) {
        array.splice(idx, 1);
      }
    };
  });



})(angular, CRM.$, CRM._);
