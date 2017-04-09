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

    function findWorkflowIndexById (id) {
      var index = null;

      $scope.workflows.forEach(function (v, i) {
        if (v.id === id) {
          index = i;
        }
      });

      // returned index can be 0 so don't just do a if (index)
      return index;
    }


    $scope.deleteWorkflow = function (id) {
      var data = {
        route_name: 'workflow_delete',
        url_params: {
          '{id}': id
        }
      };

      var request = $http.delete('/admin/generic', {
        params: data
      });
      request.success(function (data) {
        if (data.success) {
          var index = findWorkflowIndexById(id);

          if (index !== null) {
            $scope.workflows.splice(index, 1);
          }

          $scope.count = $scope.workflows.length;

          $scope.alerts.addAlert('success', 'Workflow deleted', true);
        }
        else {
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function (errors) {
        angular.forEach(errors, function (value, key) {
          $scope.alerts.addAlert('danger', value);
        });
      });
    };

  }
]);
