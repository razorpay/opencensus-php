//Single Payment Details controller
//Child of TransactionDetailCtrl
app.controller('OrderDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  'statusClass',
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost, getStatusClass) {
    $scope.getStatusClass = getStatusClass;
    $scope.payments = [];

    $scope.toggleShowPayments = function () {
      // If it was shown, just toggle it
      if ($scope.isCollapsed === false) {
        $scope.isCollapsed = true;
        return;
      }
      var request = $http.get('/' + $scope.mode + '/orders/' + $scope.entity.id + '/payments');
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.payments = data.data;
          $scope.isCollapsed = false;
        } else {
          angular.forEach(data.errors, function (error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };


  }
]);
