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

    // Get requests made by maker
    $scope.getActionsByMaker = function () {

      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_actions_by_maker',
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.workflow_requests = data.data;
        }
      }).error(function () {

      })

    };

    $scope.getActionsByMaker();

  }
]);
