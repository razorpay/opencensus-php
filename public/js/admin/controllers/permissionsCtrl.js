//Merchant List controller
app.controller('PermissionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.permissions = [];
    $scope.count = 0;

    function findOrgIndexById (id) {
      var index = null;

      $scope.permissions.forEach(function (v, i) {
        if (v.id === id) {
          index = i;
        }
      });

      // returned index can be 0 so don't just do a if (index)
      return index;
    }

    /**
     * Actions
     */

    $scope.fetchPermissions = function () {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_multiple',
          count: 1000,  /* A very high number */
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.permissions = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchPermissions();

    $scope.deletePermission = function (id) {
      var data = {
        route_name: 'permission_delete',
        url_params: {
          '{id}': id
        }
      };

      var request = $http.delete('/admin/generic', {
        params: data
      });
      request.success(function (data) {
        if (data.success) {
          var index = findOrgIndexById(id);

          if (index !== null) {
            $scope.permissions.splice(index, 1);
          }

          $scope.count = $scope.permissions.length;

          $scope.alerts.addAlert('success', 'Permission deleted', true);
        }
      });
    };

    $scope.showPermRoles = function (perm_id) {
      $modal.open({
        templateUrl: 'permissionRolesContent.html',
        controller: 'PermissionsRolesCtrl',
        resolve: {
          perm_id: function () {
            return perm_id;
          }
        }
      });
    };
  }
])
.controller('PermissionsRolesCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'perm_id',
  function($scope, $modalInstance, $http, perm_id) {
    $scope.roles = null;

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

    function showLoader() {
      $scope.showLoader = true;
    }

    function hideLoader() {
      $scope.showLoader = false;
    }

    // Fetch roles for permission
    function fetchRolesById() {
      showLoader();

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
        hideLoader();
      });
    }

    fetchRolesById();
  }
]);

