'use strict';
//Entities Listing Controller
app.controller('WorkflowRequestsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function($scope, $http, alertsFactory, $state, $modal, $stateParams, admin) {
    $scope.workflow_request_type = $stateParams.type;

    $scope.stats = {
      count: 50,
      countStart: 0,
      countEnd: 0,
      skip: 0,
    };

    $scope.next = function() {
      $scope.stats.skip += $scope.stats.count;
      $scope.regenerateList();
    };

    $scope.prev = function() {
      $scope.stats.skip -= $scope.stats.count;
      $scope.regenerateList();
    };

    $scope.getStateClass = function(state) {
      switch (state) {
        case 'approved':
          return 'approved-bg-color';
        case 'executed':
          return 'executed-bg-color';
        case 'closed':
          return 'rejected-bg-color';
        case 'rejected':
          return 'rejected-bg-color';
        case 'open':
        default:
          return 'pending-bg-color';
      }
    };

    // Get requests made by maker
    $scope.getActionsByDutyAndType = function(duty, type) {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_action_get_multiple',
          query_params: {
            duty: duty,
            type: type,
            count: $scope.stats.count,
            skip: $scope.stats.skip,
          },
        },
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.workflow_requests = data.data.items;
            $scope.stats.countStart = $scope.stats.skip + 1;
            if (data.data.count === 0) {
              $scope.stats.countEnd = $scope.stats.countStart;
            } else {
              $scope.stats.countEnd =
                $scope.stats.countStart + data.data.count - 1;
            }
            $scope.allowPrev = $scope.stats.countStart != 1;
            $scope.allowNext = $scope.stats.count == data.data.count;
          }
        })
        .error(function() {});
    };

    $scope.regenerateList = function() {
      var type = $scope.workflow_request_type;

      type = type.split('-');

      // duty, type
      $scope.getActionsByDutyAndType(type[0], type[1]);
    };

    $scope.regenerateList();

    $scope.changeWorkflowRequestUrlType = function() {
      $state.go('app.workflows.actions.list', {
        type: $scope.workflow_request_type,
      });
    };

    $scope.isSuperAdmin = admin.isSuperAdmin();
  },
]);
