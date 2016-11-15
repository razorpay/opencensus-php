//Merchant List controller
app.controller('RolesCtrl', [
  '$scope',
  '$http',
  '$modal',
  'transformRequestAsFormPost',
  'organization',
  function ($scope, $http, $modal, transformRequestAsFormPost, organization) {
    $scope.roles = [];
    $scope.count = 0;
    $scope.permissions = organization.fetchPermissions();

    /**
     *  Modals
    **/

    $scope.openEditRole= function (id) {
      $scope.selected = $scope.roles.filter(function(x) {
        return x['id'] === id;
      });
      var modalInstance = $modal.open({
        templateUrl: 'editRoleModalContent.html',
        controller: 'editRoleCtrl',
        resolve: {
          current: function () {
            return jQuery.extend({}, $scope.selected[0]);
          },
          permissions: function () {
            return jQuery.extend({}, $scope.permissions);
          },
        }
      });
      modalInstance.result.then(function (role) {
        $scope.editRole(role);
      }, $.noop);
    };


    /* TODO: use fetchRoles from factory */
    $scope.getRoles = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'role_get_multiple',
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.roles = data.data.items
          $scope.count = data.data.count;
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

    };

    $scope.getRoles();

    $scope.addRole = function (role) {
      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'role_create',
        },
        data: {
          body: {
            name: role.name,
            description: role.description
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.roles.push({
            id: data.data.id,
            name: data.data.name,
            description: data.data.description
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.deleteRole = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'role_delete',

          url_params: {
            '{roleId}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          $scope.roles = $scope.roles.filter(function (x) {
            return x.id !== id
          });
          $scope.count = $scope.roles.length;
        }
      });
    };

    $scope.editRole = function (role) {
      var data = {};
      var route_name = 'role_edit';

      data.body = {
        name: role.name,
        description: role.description,
        permissions: role.permissions,
      }
      data.route_name = route_name;

      delete data.body.route_name;

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: route_name,

          url_params: {
            '{roleId}' : role.id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          // Update the org model (todo: make this a helper)

          var index = null;

          $scope.roles.forEach(function (v, i) {
            if (v.id === data.data.id) {
              index = i;
            }
          });

          if (index !== null) {
            $scope.roles[index] = data.data;
          }

          $scope.alerts.addAlert('success', 'Role updated', true);
        }
        else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }

      });
    };
  }
]);
