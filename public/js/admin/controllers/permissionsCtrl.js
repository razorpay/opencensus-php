//Merchant List controller
app.controller('PermissionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {
    $scope.permissions = [];
    $scope.count = 0;

    /**
     * Modal openers
     */
    $scope.openAddPermissionModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addPermissionModalContent.html',
        controller: 'addPermissionModalCtrl'
      });
      modalInstance.result.then($scope.addPermission, $.noop);
    };

    /**
     * Actions
     */

    $scope.addPermission = function(permission) {
      var request = $http({
        method: 'post',
        url: '/admin/generic',
        params: {
          route_name: 'add_permission',
        },
        data: {
          body: {
            name: permission.name,
            description: permission.description
          }
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Permission added successfully. Response: ' + JSON.stringify(data.data), true);
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

    $scope.fetchPermissions = function () {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_multiple'
        }
      })

      /**
       * TODO: remove mocked data
       */

      request.success(function (data) {
        if (data.success) {
          $scope.permissions = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchPermissions();
  }
]).controller('addPermissionModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (permission) {
      $modalInstance.close(permission);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
])
