/**
 * Webhooks Ctrl
 */
app.controller('ConfigCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.config = {};

    $scope.fetchConfig = function() {

      var request = $http({
        method: 'get',
        url: '/merchants/config'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.config = data.data;
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

    // Fetch the config on load
    $scope.fetchConfig();
  }
]);
