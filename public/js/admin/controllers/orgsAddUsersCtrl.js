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
  function(
    $scope,
    $http,
    alertsFactory,
    transformRequestAsFormPost,
    $modal,
    organization,
    $stateParams,
    $state
  ) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.selected_roles = [];
    $scope.roles = [];
    $scope.roleNames = {}; // Used only as mapping between role id to names (used for selected_roles)

    // Get dynamic user fields
    var request = $http({
      url: '/admin/generic',

      method: 'GET',

      params: {
        route_name: 'org_fieldmap_get_by_entity',

        url_params: {
          '{entity}': 'admin',
        },
      },
    });

    request.success(function(data) {
      if (data.success) {
        $scope.fields = data.data.fields;
      }
    });

    $scope.fetchUser = function(id) {
      var request = $http({
        url: '/admin/generic',
        params: {
          route_name: 'admin_get',

          url_params: {
            '{adminId}': id,
          },
        },
      });

      request.success(function(data) {
        if (data.success) {
          var user = data.data;
          var userGroups = user.groups || [];
          var userRole = user.roles || [];

          userGroups.map(function(group) {
            $scope.selected_groups[group.id] = true;
          });

          userRole.map(function(role) {
            $scope.selected_roles.push(role.id);
          });

          $scope.user = user;
        }
      });
    };

    $scope.user = {};
    $scope.selected_groups = [];
    $scope.select_all = false;

    organization.fetchRoles().then(function(roles) {
      angular.forEach(roles, function(role) {
        $scope.roles.push({
          id: role.id,
          name: role.name,
        });

        $scope.roleNames[role.id] = role.name; // Create mapping id vs name
      });
    });

    organization.fetchGroups().then(function(groups) {
      $scope.groups = groups;
    });

    if ($stateParams.id) {
      $scope.fetchUser($stateParams.id);
    }

    $scope.toggleSelAll = function() {
      $scope.select_all = !$scope.select_all;
      $scope.selected_groups = {};

      if (!$scope.select_all) {
        return;
      }

      $scope.groups.map(function(group) {
        $scope.selected_groups[group.id] = true;
      });
    };

    // If an org is (un)selected, perform actions
    $scope.updateIfGroupsChanged = function(id) {
      // If group id is removed from selected_groups then set select_all tag to false
      if (!$scope.selected_groups[id]) {
        $scope.select_all = false;
      }
    };

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
        roles: $scope.selected_roles,
        groups: getSelectedGroups(),
        allow_all_merchants: user.allow_all_merchants ? '1' : '0',
      };

      // send only those fields expected by field map
      data.body = Object.keys(data.body)
        .filter(function(key) {
          return $scope.fields.indexOf(key) !== -1 ? true : false;
        })
        .reduce(function(ob, key) {
          ob[key] = data.body[key];

          return ob;
        }, {});

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: 'admin_edit',

          url_params: {
            '{adminId}': user.id,
          },
        },
      });

      request.success(function(data) {
        if (data.success) {
          if (data.data.workflow_id) {
            $state.go('app.workflows.actions.detail', {
              action_id: data.data.id,
            });
          } else {
            $scope.alerts.addAlert('success', 'User updated', true);
          }
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      });

      return request;
    };

    function getSelectedGroups() {
      var groups = [];
      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {
          if ($scope.selected_groups[key]) {
            groups.push(key);
          }
        }
      }
      return groups;
    }

    // Attach event listener for selection on custom select tag
    // Called from directive - roleSelect
    $scope.initRoleSelector = function(element) {
      element.on('select2:select', function(e) {
        var data = e.params.data;
        var elem = e.params.data.element;
        var roleId = elem.value;

        // Add role in selected_roles if not already added
        if ($scope.selected_roles.indexOf(roleId) === -1) {
          $scope.selected_roles.push(roleId);
        }

        element.val(null).trigger('change');
      });
    };

    // Remove (in view) already selected role from select dropdown
    $scope.filterRoles = function(role) {
      if ($scope.selected_roles.indexOf(role.id) > -1) {
        return false;
      }
      return true;
    };

    // Remove role which is already selected
    $scope.removeRole = function(roleId) {
      var index = $scope.selected_roles.indexOf(roleId);
      if (index > -1) {
        $scope.selected_roles.splice(index, 1);
      }
    };

    // Create new user if user is not present otherwise call edit request
    $scope.save = function(user) {
      if (user.id) {
        return $scope.editUser(user);
      }

      var body = user;
      body.groups = getSelectedGroups();
      body.roles = $scope.selected_roles;

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'admin_create',
        },
        data: {
          body: body,
        },
      });
      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Admin created successfully.',
              true
            );
            $state.go('app.users.edit', { id: data.data.id });
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });

      return request;
    };

    /**
     * Actions
     */
  },
]);
