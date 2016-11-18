//Merchant List controller
app.controller('OrgsListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.organizations = [];
    $scope.count = 0;

    function findOrgIndexById(id) {
      var index = null;

      $scope.organizations.forEach(function (v, i) {
        if (v.id === id) {
          index = i;
        }
      });

      // returned index can be 0 so don't just do a if (index)
      return index;
    }


    /**
     * Actions
     */

    // Fetch the entire org list to show in a table

    $scope.fetchOrgs = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'org_get_multiple'
        }
      });

      /**
       * TODO: remove mocked data
       */
      $scope.organizations = []
      $scope.count = 0;

      request.success(function (data) {
        if (data.success) {
          $scope.organizations = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchOrgs();

    $scope.deleteOrg = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'org_delete',

          url_params: {
            '{id}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          var index = findOrgIndexById(id);

          if (index !== null) {
            $scope.organizations.splice(index, 1);
          }

          $scope.count = $scope.organizations.length;

          $scope.alerts.addAlert('success', 'Organization deleted', true);
        }
      });
    };
  }
])
