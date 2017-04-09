// Organization Field Maps List controller
app.controller('OrgsFieldMapsListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, $stateParams, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();

    var orgId = $stateParams.id;

    $scope.orgFieldMaps = [];
    $scope.count = 0;

    function findOrgIndexById(id) {
      var index = null;

      $scope.orgFieldMaps.forEach(function (v, i) {
        if (v.id === id) {
          index = i;
        }
      });

      // returned index can be 0 so don't just do a if (index)
      return index;
    }

    // Fetch the entire fieldmaps list of an org to show in a table
    $scope.fetchOrgFieldMaps = function () {
      var data = {
        route_name: 'org_fieldmap_get_multiple',
        url_params: {
          '{orgId}' : orgId
        }
      };

      var request = $http.get('/admin/generic', {
        params: data
      });

      $scope.orgFieldMaps = []
      $scope.count = 0;

      request.success(function (data) {
        if (data.success) {
          $scope.orgFieldMaps = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchOrgFieldMaps();

    $scope.deleteFieldMap = function(id) {
      var data = {
        route_name: 'org_fieldmap_delete',
        url_params: {
          '{orgId}': orgId,
          '{id}': id
        }
      };
      var request = $http.delete('/admin/generic', {
        params: data
      });
      request.success(function (data) {
        if (data.success) {
          var index = findOrgIndexById(id);

          if (index !== null) {
            $scope.orgFieldMaps.splice(index, 1);
          }

          $scope.count = $scope.orgFieldMaps.length;

          $scope.alerts.addAlert('success', 'Field Map deleted', true);
        }
      });
    };
  }
])
