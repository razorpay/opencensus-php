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
    $scope.showColorPicker = false;

    $scope.setConfig = function(config) {
      $scope.config.brand_color = config.brand_color ? "#" + config.brand_color : null
      // This always stays as a string, except when we send it back
      $scope.config.transaction_report_email = config.transaction_report_email.join(',');
    }

    $scope.fetchConfig = function() {

      var request = $http({
        method: 'get',
        url: '/config'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.setConfig(data.data);
          if ($scope.config.brand_color !== null) {
            $scope.showColorPicker = true;
          }
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

    $scope.save = function(config) {
      var data = {
        brand_color: config.brand_color ? config.brand_color.substr(1).toUpperCase() : null,
        transaction_report_email: config.transaction_report_email ? config.transaction_report_email.split(',') : null
      }
      var request = $http({
        "method": 'PUT',
        "url": '/config',
        "data": data,
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('success', 'Configuration Updated', true);
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
  }
]);
