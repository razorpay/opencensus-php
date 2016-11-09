"use strict";

app.controller('RoleDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, $stateParams, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.id = $stateParams.id;
    $scope.role = {
      id: $stateParams.id,
    };

    fetchRole();

    function fetchRole() {
      var request = $http.get('/admin/role/' + $scope.role.id);
      $scope.role.name = 'fdfasd';
      $scope.role.description = 'Some fine description';
      request.success(function (data) {
        console.log(data.data);
      }).error(function () {
        console.log('Fetch Role Request Failed');
      });
    }

    $scope.openPermissionsListModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'permissionsListModal.html',
        controller: 'PermissionsListCtrl',
        resolve: {
          current: function () {
            return $scope.role.id;
          }
        }
      });
      modalInstance.result.then(function () {
      }, $.noop);
    };

    $scope.openAddPermissionModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addPermissionModal.html',
        controller: 'AddPermissionCtrl',
        resolve: {
          current: function () {
            return $scope.role.id;
          }
        }
      });
      modalInstance.result.then(function () {
      }, $.noop);
    };
  }
]).controller('PermissionsListCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  function ($scope, $modalInstance, $http, current) {
    var role_id = current;
    $scope.permissions = [{
      id: '6dLbNSpv5XbCOD',
      name: 'Permission name',
      description: 'Some fine description'
    }];
    var request = $http.get('/admin/' + role_id + '/permissions');
    request.success(function (data) {
      console.log(data.data);
    })
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('AddPermissionCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  'transformRequestAsFormPost',
  function ($scope, $modalInstance, $http, current, transformRequestAsFormPost) {
    $scope.roleId = current;
    $scope.permission = {};
    //TODO: Add http post request for creating permission

    $scope.ok = function (permission) {
      $modalInstance.close(permission);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    }
  }
]);
