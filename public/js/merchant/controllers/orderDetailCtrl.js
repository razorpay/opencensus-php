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

      var params = {};
      params.route_name = 'order_payments';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id
      };

      var request = $http.get('/user/generic', {
        params: params
      });

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.payments = data.data.items;
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
