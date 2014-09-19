//Single Transaction Details controller
app.controller('RefundDetailCtrl', ['$scope', '$http', '$stateParams', 'modeFactory', 'alertsFactory',
  function($scope, $http, $stateParams, modeFactory, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.refund = {
      id: $stateParams.id
    };

    fetchRefund();

    function fetchRefund() {
      var request = $http.get("/" + modeFactory.getMode() +  "/refunds/" + $scope.refund.id);

      request
      .success(function(data) {
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.refund = data.data.data[0];
        }
        else {
          angular.forEach(data.errors, function(error, key) {
            $scope.alerts.addAlert('danger', error);
          });      
        }
      })
      .error(function() {
        $scope.alerts.addAlert('danger', null, true);
      }); 
    };
}]);