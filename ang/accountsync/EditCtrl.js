(function(angular, $, _) {

  angular.module('accountsync', CRM.angular.modules)

  // To allow for multiple plugins, pass the plugin used as a parameter.
  // Default is xero for legacy reasons.
  .config(function($routeProvider) {
      $routeProvider.when('/accounts/contact/sync/:plugin?', {
        controller: 'AccountsyncEditCtrl',
        templateUrl: '~/accountsync/EditCtrl.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          suggestions: function(crmApi, $route) {

            var plugin = ('plugin' in $route.current.params)? $route.current.params.plugin: 'xero';

            return crmApi('AccountContact', 'getsuggestions', {
              plugin: plugin,
              do_not_sync: 0,
              contact_id: {'IS NULL' : 1},
              'sequential' : 1,
              'options' : {'limit' : 25, 'sort' : 'accounts_modified_date DESC'}
            });
          },
          totalCount: function(crmApi, $route) {

            var plugin = ('plugin' in $route.current.params)? $route.current.params.plugin: 'xero';

            return crmApi('AccountContact', 'getcount', {
              plugin: plugin,
              do_not_sync: 0,
              contact_id: {'IS NULL' : 1}
            });
          }
        }
      });
    }
  )

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   accountContacts -- The current account contacts, defined above in config().
  .controller('AccountsyncEditCtrl', function($scope, crmApi, crmStatus, crmUiHelp, suggestions, totalCount, $routeParams) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('accountsync');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/accountsync/EditCtrl'}); // See: templates/CRM/accountsync/EditCtrl.hlp

    var plugin = ('plugin' in $routeParams)? $routeParams.plugin: 'xero';

    // We have accountContact available in JS. We also want to reference it in HTML.
    $scope.accountContacts = suggestions.values;
    $scope.totalCount = totalCount.result;

    $scope.save = function save(accountContact) {
      var success;
      accountContact.accounts_data.civicrm_formatted.contact_type = 'Individual';
      switch (accountContact.suggestion) {
        case 'do_not_sync':
          success = crmStatus(
            {start: ts('Saving...'), success: ts('Saved')},
            crmApi('AccountContact', 'create', {
              id: accountContact.id,
              do_not_sync: 1,
              contact_id: accountContact.contact_id,
              plugin: plugin
            }).then(function(apiResult) {
              $scope.removeItem($scope.accountContacts, accountContact);
              $scope.totalCount--;
            })
          );
          break;

        case 'create_contact':
          var contactCreateParams = {};
          var contactType = crmApi('ContactType', 'getsingle', {
            return: {0: 'parent_id', 1: 'name'},
            id: accountContact.suggested_contact_type,
          }).then(function(contactType) {
            if (contactType) {
              if (contactType.parent_id) {
                contactCreateParams.contact_type = contactType.parent_id;
                contactCreateParams.contact_sub_type = contactType.name;
              }
              else {
                contactCreateParams.contact_type = contactType.id;
              }
            }

            if (contactCreateParams.contact_type == 3) {
              contactCreateParams.contact_type = 'Organization';
              contactCreateParams.organization_name = accountContact.accounts_display_name;
            }
            else if (contactCreateParams.contact_type == 1) {
              contactCreateParams.contact_type = 'Individual';
              if (!accountContact.accounts_data.civicrm_formatted.first_name && !accountContact.accounts_data.civicrm_formatted.last_name) {
                var split = accountContact.accounts_data.civicrm_formatted.display_name.split(' ');
                accountContact.accounts_data.civicrm_formatted.first_name = split.shift();
                accountContact.accounts_data.civicrm_formatted.last_name = split.join(' ');
              }
              contactCreateParams.first_name = accountContact.accounts_data.civicrm_formatted.first_name;
              contactCreateParams.last_name = accountContact.accounts_data.civicrm_formatted.last_name;
            }
            contactCreateParams['api.AccountContact.create'] = {id: accountContact.id, accounts_needs_update: 1, plugin: plugin};
            success = crmStatus(
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
          });

          break;

        case 'link_contact':
          success = crmStatus(
            {start: ts('Saving...'), success: ts('Saved')},
            crmApi('AccountContact', 'create', {
              'id' : accountContact.id,
              'contact_id' : accountContact.suggested_contact_id,
              'accounts_needs_update' : 1,
            })
          )
            .then(function(apiResult) {
                $scope.totalCount--;
                $scope.removeItem($scope.accountContacts, accountContact);
              },
              function(apiResult) {
                accountContact.suggestion = 'do_not_sync';
                accountContact.is_error = true;
              });
          break;

      }

      var nextContact = crmApi('AccountContact', 'getsuggestions', {
          'id' : {'>' : $scope.accountContacts[$scope.accountContacts.length -1]['id']},
          'plugin' : plugin,
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
