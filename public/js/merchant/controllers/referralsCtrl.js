/**
 * Referrals Ctrl
 */
app.controller('ReferralsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    var fetchReferrals = function() {
      var request = $http({
        method: 'get',
        url: '/referrals'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.referrals = data.data;
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

    $scope.createMerchant = function(merchant) {
      var request = $http({
        method: 'post',
        url: '/submerchants',
        data: merchant
      });

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.alerts.addAlert('success', 'Merchant was created successfully');
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    fetchReferrals();

    $scope.openCreateMerchant = function() {
      var modalInstance = $modal.open({
        templateUrl: 'createMerchantModal.html',
        controller: 'createMerchantCtrl'
      });
      modalInstance.result.then($scope.createMerchant, $.noop);
    };

}]).controller('createMerchantCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.merchant = {
      name: ''
    };
    $scope.ok = function (merchant) {
      $modalInstance.close(merchant);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
