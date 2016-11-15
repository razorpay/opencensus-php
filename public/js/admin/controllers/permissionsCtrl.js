//Merchant List controller
app.controller('PermissionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {
    $scope.permissions = [];
    $scope.count = 0;

    /**
     * Actions
     */

    $scope.fetchPermissions = function () {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_multiple',
          count: 1000,  /* A very high number */
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.permissions = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchPermissions();
  }
]);
