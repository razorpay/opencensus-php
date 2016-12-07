"use strict";

//Audit Log List controller
app.controller('AuditlogsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.invitations = [];
    $scope.count = 0;

    $scope.audit_logs = [];

    $scope.audit_log_cache = {};

    $scope.fetchAuditLogs = function () {

      organization.fetchCurrentOrg().then(function (data) {

        var request = $http({
          url: '/admin/generic',
          params: {
            route_name: 'auditlog_search',
            url_params: {
              '{id}' : data.id
            }
          }
        });

        request.success(function (data) {
          if (data.success) {
            $scope.audit_logs = data.data;

            $scope.audit_logs.forEach(function (v, i) {
              $scope.audit_log_cache[v.id] = v.event;
            });
          }
        });

      });
    }

    $scope.fetchAuditLogs();


    // Audit log breakup details

    $scope.showAuditLogDetail = function (audit_log_id) {
      $modal.open({
        templateUrl: 'auditLogDetailContent.html',
        controller: 'auditLogDetailCtrl',
        resolve: {
          audit_log_id: function () {
            return audit_log_id;
          },

          audit_log_cache: function () {
            return $scope.audit_log_cache;
          }
        }
      });
    };
  }
])
.controller('auditLogDetailCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'audit_log_id',
  'audit_log_cache',
  function($scope, $modalInstance, $http, audit_log_id, audit_log_cache) {
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

    $scope.audit_log_id = audit_log_id;

    // TODO: Caching
    $scope.data = audit_log_cache[audit_log_id];
  }
]);
