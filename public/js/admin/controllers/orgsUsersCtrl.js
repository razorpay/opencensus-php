//Admin List controller
app.controller('OrgsUsersCtrl', [
  '$scope',
  '$http',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  'organization',
  function ($scope, $http, $modal, alertsFactory, transformRequestAsFormPost,
    organization) {
    $scope.users = [];
    $scope.count = 0;
    $scope.alerts = alertsFactory.getHandler();
    $scope.roles = organization.fetchRoles();
    $scope.groups = organization.fetchGroups();

    /**
     *  Modals
    **/

    $scope.openEditOrgUser = function (id) {
      $scope.selected = $scope.users.filter(function(x) { return x['id'] === id; });
      var modalInstance = $modal.open({
        templateUrl: 'editOrgUserModalContent.html',
        controller: 'editOrgUserModalCtrl',
        resolve: {
          current: function () {
            return jQuery.extend({}, $scope.selected[0]);
          },
          roles: function () {
            return jQuery.extend(true, {}, $scope.roles);
          },
          groups: function () {
            return jQuery.extend(true, {}, $scope.groups);
          },

        }
      });
      modalInstance.result.then(function (users) {
        $scope.editUser(users);
      }, $.noop);
    };

    /**
     *  Actions
    **/

    $scope.listUsers = function() {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'admin_get_multiple',
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.users = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.listUsers();

    $scope.editUser = function(user) {
      var route_name = 'admin_edit';
      var data = {};
      data.body = {
        name: user.name,
        email: user.email,
        username: user.username,
        department_code: user.department_code,
        branch_code: user.branch_code,
        location_code: user.location_code,
        supervisor_code: user.supervisor_code,
        disabled: user.disabled + 0,
        roles: user.roles,
        groups: user.groups
      };
      data.route_name = route_name;

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: route_name,

          url_params: {
            '{adminId}': user.id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */

        if (data.success) {
          var index = null;

          $scope.users.forEach(function (v, i) {
            if (v.id === data.data.id) {
              index = i;
            }
          });

          if (index !== null) {
            $scope.users[index] = data.data;
          }

          $scope.alerts.addAlert('success', 'User updated', true);
        } else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      });
    }

    $scope.deleteOrgUser = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'admin_delete',

          url_params: {
            '{adminId}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          $scope.users = $scope.users.filter(function (x) {
            return x.id !== id
          });
          $scope.count = $scope.users.length;
        }
      });
    }
  }
]).controller('newAdminModalCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.ok = function (data) {
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('editOrgUserModalCtrl', [
  '$scope',
  '$modalInstance',
  'current',
  'roles',
  'groups',
  function ($scope, $modalInstance, current, roles, groups) {

    current.locked = !!current.locked;
    current.disabled = !!current.disabled;
    $scope.roles = roles;
    $scope.groups = groups;
    $scope.selected_groups = {};
    $scope.select_all = false;

    current.roles = current.roles.map(function(role){
      role.id = 'role_' + role.id;
      return role;
    })

    if (current.roles.length) {
      current.role = current.roles[0].id;
    }

    current.groups = current.groups.map(function(group){
      group.id = 'grp_' + group.id;
      $scope.selected_groups[group.id] = true;
      return group;
    })

    $scope.user = current;

    $scope.selectAll = function() {
      $scope.selected_groups = {};
      $scope.select_all = !$scope.select_all;

      if (!$scope.select_all) {
        return;
      }

      for (var key in $scope.groups) {
        if ($scope.groups.hasOwnProperty(key)) {
          var group = $scope.groups[key];
          $scope.selected_groups[group.code] = true;
        }
      }
    }

    $scope.ok = function (user) {
      user.groups = [];
      user.roles = [];

      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            user.groups.push(key);
          }
        }
      }

      user.roles.push(user.role);

      $modalInstance.close(user);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
