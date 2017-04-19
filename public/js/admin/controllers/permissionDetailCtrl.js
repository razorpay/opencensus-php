app.controller('PermissionDetailCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$state',
  'transformRequestAsFormPost',
  'utils',
  function ($scope, $http, alertsFactory, $stateParams, $state, transformRequestAsFormPost, utils) {
    $scope.localOrgs = {};
    $scope.RZid = 'org_100000razorpay';

    // Map and merge the org data to local org structure
    function mapOrgsToLocal(selOrgList) {
      var tmpAllOrgs = {};
      var tmpSelOrgs = {};

      // Prepare all org list
      if ($scope.organizations && Object.keys($scope.organizations).length) {
        tmpAllOrgs = $scope.organizations.reduce(function(result, item) {
          result[item.id] = false;
          return result;
        }, {});
      }

      // Prepare previously selected org list
      if (selOrgList && Object.keys(selOrgList).length) {
        tmpSelOrgs = selOrgList.reduce(function(result, item) {

          result[item.id] = true;
          return result;
        }, {});

        // True only if previously selected
        if (tmpSelOrgs.hasOwnProperty($scope.RZid)) {
          $scope.freezeRZ = true;
        }
      }

      $scope.localOrgs = utils.concatObj(tmpAllOrgs, tmpSelOrgs, $scope.localOrgs); // Don't change order for tmpAllOrgs, tmpSelOrgs
    }

    // Fetch orgs having permissions
    $scope.fetchPermission = function (id) {
      var data = {
        route_name: 'permission_get',
        url_params: {
          '{id}' : id
        }
      };

      var request = $http.get('/admin/generic', {
        params: data
      });

      request.success(function (data) {
        if (data.success) {
          var permission = data.data;
          $scope.permission = permission;
          mapOrgsToLocal($scope.permission.orgs); // Update the list view with previously selected organizations
        }
      });
    };

    // (De)Select all oraganizations
    $scope.toggleSelAll = function() {
      $scope.select_all = !$scope.select_all;
      if (!$scope.select_all) {
        $scope.organizations.map(function(perm) {
          if (perm.id === $scope.RZid && $scope.freezeRZ) {
            return;
          }
          $scope.localOrgs[perm.id] = false;
        });
      } else {
        $scope.organizations.map(function(perm) {
          $scope.localOrgs[perm.id] = true;
        });
      }
    };

    // Deselect in view
    $scope.updateSelAllTag = function(id)   {
      if (!$scope.localOrgs[id]) {
        $scope.select_all = false;
      }
    };

    // Fetch entire list of org
    function fetchOrgs() {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'org_get_multiple'
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.organizations = data.data.items;
          mapOrgsToLocal(); // add all orgs in list view
        }
      });
    };

    fetchOrgs();

    $scope.roles = null;
    // Fetch roles for corresponding permission id
    function fetchRolesById(perm_id) {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_roles',
          url_params: {
            '{id}': perm_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.roles = data.data.items;

        }
      });

      request.finally(function () {
      });
    }

    if ($stateParams.id) {
      $scope.fetchPermission($stateParams.id)
      fetchRolesById($stateParams.id)
    }

    $scope.save = function (permission) {
      // dont trust user check
      if($scope.freezeRZ) {
        $scope.localOrgs[$scope.RZid] = true;
      }

      // Remove keys with false value
      permission.orgs = Object.keys($scope.localOrgs).filter(function(ele){
        return $scope.localOrgs[ele];
      });

      // edit
      if (permission.id) {
        var data = {
          route_name: 'permission_edit',
          url_params: {
            '{id}' : $scope.permission.id
          }
        };
        data.body = jQuery.extend(true, {}, permission);
        delete data.body.id;

        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost,
        });
      }
      // add
      else {
        var data = {
          route_name: 'permission_create',
          body: permission
        };

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost,
        });
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Permission saved successfully.', true);
          $state.go('app.permissions.edit', {id: data.data.id});

          // Freeze if RZ added in org (required on edit url)
          if ($scope.localOrgs.hasOwnProperty($scope.RZid) && !$scope.freezeRZ) {
            $scope.freezeRZ = true;
          }
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
    };
  }
])
