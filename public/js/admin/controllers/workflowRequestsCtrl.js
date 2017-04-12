"use strict";
//Entities Listing Controller
app.controller('WorkflowRequestsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin) {

    $scope.workflow_request_type = $stateParams.type;

    // Get requests made by maker
    $scope.getActionsByMakerAndType = function (type) {

      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_actions_by_maker',
          query_params: {
            type: type
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.workflow_requests = data.data.items;
        }
      }).error(function () {

      });

    };

    $scope.getCheckerActions = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_actions_for_checker',
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.workflow_requests = data.data.items;
        }
      }).error(function () {

      });
    };

    $scope.regenerateList = function () {
      var type = $scope.workflow_request_type;

      switch (type) {
        case 'checker': $scope.getCheckerActions(); break;
        case 'closer': $scope.getActionsByMakerAndType('closed'); break;
        case 'open': $scope.getActionsByMakerAndType('open'); break;
        case 'all':
        default: $scope.getActionsByMakerAndType('all');
      }
    };

    $scope.regenerateList();

    $scope.changeWorkflowRequestUrlType = function () {
      $state.go('app.workflows.actions.list', { type: $scope.workflow_request_type });
    };
    $scope.isSuperAdmin = admin.isSuperAdmin();
  }
]);
