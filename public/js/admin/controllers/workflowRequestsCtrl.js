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
          },
        },
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.workflow_requests = data.data.items;
          }
        })
        .error(function() {});
    };

    $scope.regenerateList = function() {
      var type = $scope.workflow_request_type;

      var duty = 'maker'; // Considering default duty as Maker since most requests are for maker.

      if (type === 'checker') {
        duty = type;
      }

      $scope.getActionsByDutyAndType(duty, type);
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
