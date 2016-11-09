//Merchant List controller
app.controller('PermissionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
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
        url: '/permissions',
        transformRequest: transformRequestAsFormPost,
        data: permission
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
      var request = $http.get('/permissions');

      /**
       * TODO: remove mocked data
       */
      $scope.permissions = [{
        id: '2sGsPw5xI4aNn',
        name: 'Super',
        description: 'Can do anything',
      },{
        id: 'Uy3A6sNq0P5hA',
        name: 'Normal',
        description: 'Can do some things',
      }]

      request.success(function (data) {
        if (data.success) {
          $scope.permissions = data.data.data;
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
