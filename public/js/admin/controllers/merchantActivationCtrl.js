//Merchant Activation Detail Display Controller
app.controller('MerchantActivationCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  function ($scope, $http, $stateParams, alertsFactory) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.merchant = { id: $stateParams.id };
    $scope.check = {};
    $scope.data = {
      1: {},
      2: {},
      3: {},
      4: {},
      5: {},
      6: {}
    };
    $scope.files = {};
    $scope.locked = true;
    getData();
    function getData() {
      var request = $http.get('/admin/merchant/' + $scope.merchant.id + '/details');
      request.success(function (data) {
        if (data.success) {
          angular.forEach(data.data.merchant.steps_finished, function (value, key) {
            $scope.check[value] = true;
          });
          angular.forEach(data.data.activation.data, function (value, key) {
            $scope.data[key] = value;
          });
          angular.forEach(data.data.activation.files, function (value, key) {
            $scope.files[key] = value;
          });
          $scope.merchant = data.data.merchant;
        } else {
          $scope.alerts.addAlert('danger');
        }
      }).error(function () {
        $scope.alerts.addAlert('danger');
      });
    }
  }
]);