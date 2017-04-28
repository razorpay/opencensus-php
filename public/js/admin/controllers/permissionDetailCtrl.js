app.controller('PermissionDetailCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$state',
  'utils',
  function($scope, $http, alertsFactory, $stateParams, $state, utils) {
    $scope.select_all = false;
    $scope.selected_organizations = {};
    $scope.workflow_orgs = {}; // Workflow enabled/disabled field corresponding to each org

    // Fetch orgs having permissions
    $scope.fetchPermission = function(id) {
      var data = {
        route_name: 'permission_get',
        url_params: {
          '{id}': id,
        },
      };

      var request = $http.get('/admin/generic', {
        params: data,
      });

      request.success(function(data) {
        if (data.success) {
          var permission = data.data;
          $scope.permission = permission;

          permission.orgs.forEach(function(org) {
            $scope.selected_organizations[org.id] = true;
          });

          // auto-select workflow_orgs from the response payload
          permission.workflow_orgs.forEach(function(org) {
            $scope.workflow_orgs[org.id] = true;
          });
        }
      });
    };

    // (De)Select all oraganizations
    $scope.toggleSelAll = function() {
      $scope.select_all = !$scope.select_all;
      $scope.selected_organizations = {};

      if (!$scope.select_all) {
        $scope.workflow_orgs = {}; // Empty workflow_orgs if toggleAll checkbox is turned off
        return;
      } else {
        $scope.organizations.map(function(perm) {
          $scope.selected_organizations[perm.id] = true;
        });
      }
    };

    // If an org is (un)selected, perform actions
    $scope.updateIfOrgsChanged = function(id) {
      // If org id is removed from selected_organizations then set select_all tag to false
      if (!$scope.selected_organizations[id]) {
        $scope.select_all = false;
      }

      // Auto unselect workflow enable tag for corresponding org if this org is (un)selected
      if ($scope.workflow_orgs[id]) {
        $scope.workflow_orgs[id] = false;
      }
    };

    // Fetch entire list of org
    function fetchOrgs() {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'org_get_multiple',
        },
      });

      request.success(function(data) {
        if (data.success) {
          $scope.organizations = data.data.items;
        }
      });
    }

    fetchOrgs();

    $scope.roles = null;
    // Fetch roles for corresponding permission id
    function fetchRolesById(perm_id) {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_roles',
          url_params: {
            '{id}': perm_id,
          },
        },
      });

      request.success(function(data) {
        if (data.success) {
          $scope.roles = data.data.items;
        }
      });
    }

    if ($stateParams.id) {
      $scope.fetchPermission($stateParams.id);
      fetchRolesById($stateParams.id);
    }

    $scope.save = function(permission) {
      // Remove keys with false value
      permission.orgs = Object.keys(
        $scope.selected_organizations
      ).filter(function(key) {
        return $scope.selected_organizations[key];
      });

      // Safe check way to include only those workflow_org which have corresponding org id in selected_organizations.
      permission.workflow_orgs = permission.orgs.filter(function(id) {
        return $scope.workflow_orgs[id];
      });

      // edit
      if (permission.id) {
        var data = {
          route_name: 'permission_edit',
          content_type: 'application/json',
          url_params: {
            '{id}': $scope.permission.id,
          },
        };
        data.body = jQuery.extend(true, {}, permission);
        delete data.body.id;

        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
      } else {
        // add
        var data = {
          route_name: 'permission_create',
          body: permission,
        };

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
      }

      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Permission saved successfully.',
              true
            );
            $state.go('app.permissions.edit', { id: data.data.id });
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
  },
]);
