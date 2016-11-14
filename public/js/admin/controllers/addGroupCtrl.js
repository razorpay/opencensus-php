app.controller('AddGroupCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {

    $scope.groups = organization.fetchGroups();
    $scope.users = organization.fetchUsers();
    $scope.role = {};
    $scope.selected_permissions = [];

    /**
     * Actions
     */

    $scope.save = function (role, permissions) {
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

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Role added', true);
        } else {
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
])
