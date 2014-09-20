//Single Transaction Details controller
app.controller('TransactionDetailCtrl', ['$scope', '$http', '$stateParams', 'modeFactory', 'alertsFactory',
  function($scope, $http, $stateParams, modeFactory, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.transaction = {
      id: $stateParams.id
    };

    fetchTransaction();

    function fetchTransaction() {
      var request = $http.get("/" + modeFactory.getMode() +  "/transactions/" + $scope.transaction.id);

      request
      .success(function(data) {
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.transaction = data.data.data[0];
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