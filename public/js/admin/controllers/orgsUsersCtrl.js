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
            return jQuery.extend(true, {}, $scope.selected[0]);
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

    if (current.roles.length) {
      current.role = current.roles[0].id;
    }

    current.groups.map(function(group){
      $scope.selected_groups[group.id] = true;
      return group;
    })

    $scope.user = current;

    $scope.selectAll = function() {
      /**
       * I'm using a hack here, for some reason the ng-model for select_all
       * was not working in the modal. The state is being maintained in the
       * controller
       */

      $scope.selected_groups = {};
      $scope.select_all = !$scope.select_all;

      if (!$scope.select_all) {
        return;
      }

      for (var key in $scope.groups) {
        if ($scope.groups.hasOwnProperty(key)) {
          var group = $scope.groups[key];
          $scope.selected_groups[group.id] = true;
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
