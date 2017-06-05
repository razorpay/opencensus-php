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
        case 'executed':
          return 'approved-bg-color';
          break;
        case 'closed':
          return 'rejected-bg-color';
          break;
        case 'rejected':
          return 'rejected-bg-color';
          break;
        case 'open':
        default:
          return 'pending-bg-color';
      }
    };

    // Get requests made by maker
    $scope.getActionsByMakerAndType = function(type) {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_actions_by_maker',
          query_params: {
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

    $scope.getCheckerActions = function() {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_actions_for_checker',
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

      if (type === 'checker') {
        $scope.getCheckerActions();
      } else {
        $scope.getActionsByMakerAndType(type);
      }
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
