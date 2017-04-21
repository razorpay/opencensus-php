app.controller('PermissionDetailCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$state',
  'transformRequestAsFormPost',
  'utils',
  function(
    $scope,
    $http,
    alertsFactory,
    $stateParams,
    $state,
    transformRequestAsFormPost,
    utils
  ) {
    $scope.select_all = false;
    $scope.selected_organizations = {};

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
        }
      });
    };

    // (De)Select all oraganizations
    $scope.toggleSelAll = function() {
      $scope.select_all = !$scope.select_all;
      $scope.selected_organizations = {};

      if (!$scope.select_all) {
        return;
      } else {
        $scope.organizations.map(function(perm) {
          $scope.selected_organizations[perm.id] = true;
        });
      }
    };

    // Deselect in view
    $scope.updateSelAllTag = function(id) {
      if (!$scope.selected_organizations[id]) {
        $scope.select_all = false;
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

      // edit
      if (permission.id) {
        var data = {
          route_name: 'permission_edit',
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
          transformRequest: transformRequestAsFormPost,
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
          transformRequest: transformRequestAsFormPost,
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
