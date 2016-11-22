app.controller('OrgsAddRolesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  '$stateParams',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization, $stateParams) {

    $scope.permissions = organization.fetchPermissions();
    $scope.role = {};
    $scope.selected_permissions = {};
    $scope.select_all = false;

    var role_id = $stateParams.id;

    if (role_id) {
      // Get role details
      $scope.role_id = role_id;

      var request = $http({
        url: '/admin/generic',

        method: 'GET',

        params: {
          route_name: 'role_get',

          url_params: {
            '{roleId}' : role_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.role = {
            name: data.data.name,
            description: data.data.description,
          };

          data.data.permissions.forEach(function (permission) {
            $scope.selected_permissions[permission.id] = true;
          });
        }
      });
    }

    /**
     * Actions
     */

    $scope.selectAll = function() {
      $scope.selected_permissions = {};

      if (!$scope.select_all) {
        return;
      }

      $scope.permissions.map(function(perm){
        $scope.selected_permissions[perm.id] = true;
      });
    }

    $scope.save = function (role) {
      var body = role;

      body.permissions = [];

      // selected_permissions will be like:
      // { perm_id: true, perm_id2: false, perm_id3: true, ... }

      for (var key in $scope.selected_permissions) {
        if ($scope.selected_permissions.hasOwnProperty(key)) {

          if ($scope.selected_permissions[key]) {
            body.permissions.push(key);
          }

        }
      }

      organization.addOrEditRole(body).then(function(data) {
        $scope.alerts.addAlert('success', 'Role saved', true);
      }).catch(function(errors){
        $scope.alerts.resetAlerts();

        angular.forEach(errors, function (value, key) {
          $scope.alerts.addAlert('danger', value);
        });
      })
    }
  }
]);
