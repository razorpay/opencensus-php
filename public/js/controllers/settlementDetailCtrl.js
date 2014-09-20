//Single Settlement Details controller
app.controller('SettlementDetailCtrl', ['$scope', '$http', '$stateParams', 'modeFactory', 'alertsFactory',
  function($scope, $http, $stateParams, modeFactory, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.settlement = {
      id: $stateParams.id
    };

    fetchSettlement();

    function fetchSettlement() {
      var request = $http.get("/" + modeFactory.getMode() +  "/settlements/" + $scope.settlement.id);

      request
      .success(function(data) {
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.settlement = data.data.data[0];
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