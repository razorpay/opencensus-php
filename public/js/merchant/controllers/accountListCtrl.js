app.controller('AccountListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  function ($scope, $http, alertsFactory, $state, $modal) {

    $scope.createAccount = function(account) {
      account.account = true;
      var request = $http({
        method: 'post',
        url: '/submerchants',
        data: account
      });

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.addDetails(data.data.id);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.openAddAccountModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addAccountModal.html',
        controller: 'AddAccountCtrl'
      });
      modalInstance.result.then($scope.createAccount, $.noop);
    };

    $scope.addDetails = function (accountId) {
      var modalInstance = $modal.open({
        templateUrl: 'tpl/app_accounts_activation_modal.html',
        controller: 'AccountActivationCtrl',
        size: 'lg',
        scope: function() {
            var scope = $scope.$new();
            scope.account = accountId;
            return scope;
        }(),
      });

      // regenerate account list when this modal is closed.
      modalInstance.result.finally($scope.regenerate, $.noop);
    };

    $scope.exportAccountsCSV = function () {
        $scope.alerts.addAlert('success', 'Your file will download shortly', true);

        var data = {'year': '2017', 'month': '1'};
        var request = $http({
            method: 'GET',
            url: '/' + $scope.mode + '/reports/account',
            params: data
         });

        request.success(function (data) {
          $scope.alerts.resetAlerts();
          if (data && data.success === true) {
             location.href = data.data.url;
          }
          else {
            $scope.alerts.addAlert('danger', 'An error occurred while exporting the data');
          }
        }).error(function (data) {
          $scope.alerts.addAlert('danger', 'An error occurred while exporting the data');
        });
    };
  }
])

.controller('AddAccountCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {

    $scope.account = {
      name: '',
      email: ''
    };

    $scope.add = function (account) {
      $modalInstance.close(account);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
