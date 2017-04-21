'use strict';

//Merchant List controller
app.controller('EmailLogsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$modal',
  'utils',
  function($scope, $http, alertsFactory, $modal, utils) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.emailLogs = [];

    $scope.fetchEmailLogs = function() {
      var query = { ascending: 'no' };
      if ($scope.event) {
        query.event = $scope.event;
      }
      if ($scope.recipient) {
        query.recipient = $scope.recipient;
      }
      if ($scope.tags) {
        query.tags = $scope.tags;
      }
      var request = $http.get('/admin/emaillogs', { params: query });

      request
        .success(function(data) {
          if (data.success) {
            $scope.emailLogs = data.data.items;
          } else {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function(res) {
          $scope.alerts.addAlert('danger', res ? res : null, true);
        });
    };

    $scope.fetchEmailLogs();
  },
]);
