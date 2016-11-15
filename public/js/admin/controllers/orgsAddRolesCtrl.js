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

      if ($scope.role_id) {
        // Edit

        var request = $http({
          url: '/admin/generic',
          method: 'PUT',
          params: {
            route_name: 'role_edit',

            url_params: {
              '{roleId}' : $scope.role_id
            }
          },
          data: {
            body: body
          }
        });
      }
      else {
        // Create

        var request = $http({
          url: '/admin/generic',
          method: 'POST',
          params: {
            route_name: 'role_create'
          },
          data: {
            body: body
          }
        });
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Role saved', true);
        }
        else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);
