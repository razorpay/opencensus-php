app.controller('WorkflowListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  '$stateParams',
  '$state',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization, $stateParams, $state) {

    $scope.alerts = alertsFactory.getHandler();

    $scope.workflows = [];
    $scope.count = 0;

    $scope.fetchWorkflows = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'workflow_get_multiple'
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.workflows = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchWorkflows();

  }
]);
