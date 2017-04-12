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

    // TODO: rewrite the pager logic, it sucks atm!

    $scope.pager = {
      allowPrev: true,
      allowNext: true,
      count: 100,
      skip: 0
    };

    $scope.prev = function () {
      $scope.pager.skip -= $scope.pager.count;

      $scope.fetchAuditLogs();
    };

    $scope.next = function () {
      $scope.pager.skip += $scope.pager.count;

      $scope.fetchAuditLogs();
    };

    $scope.fetchAuditLogs = function () {

      organization.fetchCurrentOrg().then(function (data) {

        if ($scope.pager.skip < 0) {
          return;
        }

        if ($scope.pager.skip === 0) {
          $scope.pager.allowPrev = false;
        }
        else {
          $scope.pager.allowPrev = true;
        }

        $scope.count = 0;
        $scope.audit_logs = [];
        $scope.audit_log_cache = {};

        var request = $http({
          url: '/admin/generic',
          params: {
            route_name: 'auditlog_search',
            query_params: {
              count: $scope.pager.count,
              skip: $scope.pager.skip
            }
          }
        });

        request.success(function (data) {
          if (data.success) {
            $scope.audit_logs = data.data;

            $scope.audit_logs.forEach(function (v, i) {
              $scope.audit_log_cache[v.id] = v.event;
            });

            if ((!data.data.length && $scope.pager.skip >= 100) ||
                (data.data.length < $scope.pager.count)){
              $scope.pager.allowNext = false;
            }
            else {
              $scope.pager.allowNext = true;
            }
          }
        });

      });
    };

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
  'utils',
  function($scope, $modalInstance, $http, audit_log_id, audit_log_cache, utils) {
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

    $scope.audit_log_id = audit_log_id;

    $scope.data = audit_log_cache[audit_log_id];

    $scope.dataKeys = [];
    if ($scope.data.entity && $scope.data.entity.change) {
      var oldData = $scope.data.entity.change.old ? $scope.data.entity.change.old : [];
      var newData = $scope.data.entity.change.new ? $scope.data.entity.change.new : [];
      $scope.dataKeys  = utils.mergeUnique(Object.keys(oldData).concat(Object.keys(newData)));
    }
  }
]);
