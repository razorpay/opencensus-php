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
      $scope.role.permissions = [
        {
          id : '6dLbNSpv5XbCOD',
          name : 'test_permission',
          description : 'Permission description'
        }
      ];
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
      var permissions = $scope.role.permissions;
      var modalInstance = $modal.open({
        templateUrl: 'addPermissionModal.html',
        controller: 'AddPermissionCtrl',
        resolve: {
          current: function () {
            return permissions;
          }
        }
      });
      modalInstance.result.then(function (selected_permissions) {
        console.log("SELECTED_PERMISSIONS", selected_permissions);
        $scope.addPermissions(selected_permissions);
      }, $.noop);
    };

    $scope.addPermissions = function () {
      // TODO code to make add permissions request
    }
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
    $scope.assigned_permissions = current;
    $scope.permissions = fetchPermissions();
    //TODO: Add http post request for creating permission

    $scope.ok = function (permissions) {
      var selected_permissions = permissions.filter(function (permission) {
        return permission.selected;
      });
      $modalInstance.close(selected_permissions);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

    function fetchPermissions() {
      return [{
      id: "dasdeer",
      name: "permission 1",
      description: "permission description"
      }, {
      id: "uydadsa",
      name: "permission 2",
      description: "permission description"
      }];
    }
  }
]);
