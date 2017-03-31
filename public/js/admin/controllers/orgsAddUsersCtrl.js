//Merchant List controller
app.controller('OrgsAddUsersCtrl', [
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

    $scope.fetchUser = function(id) {
      var request = $http({
        url: '/admin/generic',
        params: {
          route_name: 'admin_get',

          url_params: {
            '{adminId}' : id
          }
        }
      });

      request.success(function(data) {
        if (data.success) {
          var user = data.data
          var userGroups = user.groups || []
          var userRole = user.roles && user.roles[0] && user.roles[0].id

          userGroups.map(function(group){
            $scope.selected_groups[group.id] = true;
          });

          $scope.role = userRole
          $scope.user = user
        }
      })
    }

    $scope.user = {};
    $scope.selected_groups = [];
    $scope.select_all = false;
    $scope.role = '';

    organization.fetchRoles().then(function(roles) {
      $scope.roles = {};

      angular.forEach(roles, function (role) {
        $scope.roles[role.id] = role.name;
      });
    });

    organization.fetchGroups().then(function (groups) {
      $scope.groups = groups;
    });

    if ($stateParams.id) {
      $scope.fetchUser($stateParams.id)
    }

    $scope.selectAll = function() {
      $scope.selected_groups = {};

      if (!$scope.select_all) {
        return;
      }

      $scope.groups.map(function(group){
        $scope.selected_groups[group.id] = true;
      });
    }

    $scope.editUser = function(user) {
      var data = {};
      data.body = {
        name: user.name,
        //email: user.email,
        //username: user.username,
        department_code: user.department_code,
        branch_code: user.branch_code,
        location_code: user.location_code,
        supervisor_code: user.supervisor_code,
        disabled: user.disabled + 0,
        roles: getSelectedRoles(),
        groups: getSelectedGroups(),
        allow_all_merchants: user.allow_all_merchants ? "1" : "0"
      };

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: 'admin_edit',

          url_params: {
            '{adminId}': user.id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'User updated', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      });

      return request;
    }

    function getSelectedGroups() {
      var groups = []
      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {
          if ($scope.selected_groups[key]) {
            groups.push(key);
          }
        }
      }
      return groups
    }

    function getSelectedRoles() {
      var roles = []
      if ($scope.role) {
        roles.push($scope.role)
      }
      return roles
    }

    $scope.save = function(user) {
      if (user.id) {
        return $scope.editUser(user)
      }

      var body = user;
      body.groups = getSelectedGroups();
      body.roles = getSelectedRoles();

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'admin_create',
        },
        data: {
          body: body
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin created successfully.', true);
          $state.go('app.users.edit', {id: data.data.id});
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

      return request;
    }

    /**
     * Actions
     */
  }
])
