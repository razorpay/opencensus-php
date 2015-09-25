//Single Entity Details controller
app.controller('EntityDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  function ($scope, $http, $stateParams, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity = { id: $stateParams.id };
    $scope.generate = function (entity) {
      $scope.entity.type = entity;
      fetchEntity();
    };
    function fetchEntity() {
      var request = $http.get('/' + $scope.mode + '/' + $scope.entity.type + 's/' + $scope.entity.id);
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity = data.data.items[0];
        } else {
          angular.forEach(data.errors, function (error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);