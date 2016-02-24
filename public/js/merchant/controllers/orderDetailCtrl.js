//Single Payment Details controller
//Child of TransactionDetailCtrl
app.controller('OrderDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost) {
    $scope.payments = [];
    $scope.paymentsFetched = false;
    $scope.getStatusClass = function (status) {
      var mapper = {
        // Common to both payments and order
        created: 'bg-light',

        // Only for orders
        attempted: 'bg-info',
        paid: 'bg-success',

        // Only for payments
        authorized: 'bg-info',
        captured: 'bg-success',
        refunded: 'bg-primary',
        failed: 'bg-danger'
      };
      return mapper[status];
    };

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
          $scope.paymentsFetched = true;
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
