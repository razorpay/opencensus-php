"use strict";

//Audit Log List controller
app.controller('AuditlogsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.invitations = [];
    $scope.count = 0;

    $scope.fetchAuditLogs = function () {
      var request = $http.get('/admin/auditlogs');

      $scope.audit_logs = []
      $scope.count = 0;

      request.success(function (data) {
        if (data.success) {
          $scope.audit_logs = data.data;
        }
      });
    }

    $scope.fetchAuditLogs();
  }
])
