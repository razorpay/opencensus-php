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
    }
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
