//Admin List controller
app.controller('AdminsCtrl', [
  '$scope',
  '$http',
  '$modal',
  'admin',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, admin, alertsFactory, transformRequestAsFormPost) {
    $scope.admins = {};
    $scope.alerts = alertsFactory.getHandler();
    admin.identity().then(function (data) {
      $scope.admin = data;
    });
    generateTable();
    $scope.delete = function (id) {
      var request = $http.delete('/admin/users/' + id);
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin deleted successfully', true);
          generateTable();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.promote = function (id) {
      var request = $http.post('/admin/users/' + id + '/superadmin');
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin promoted successfully', true);
          generateTable();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.openEditAdmin = function (id) {
      // Leaving this commented code here since this looks better and would like to make it work.
      // $scope.selected = $scope.admins.find(admin => admin.id === id);
      $scope.selected = $scope.admins.filter(function(x) { return x['id'] === id; });
      var modalInstance = $modal.open({
        templateUrl: 'editAdminModalContent.html',
        controller: 'editAdminModalCtrl',
        resolve: {
          current: function () {
            return jQuery.extend({}, $scope.selected[0]);
          }
        }
      });
      modalInstance.result.then(function (admin) {
        $scope.editAdmin(admin);
      }, $.noop);
    };
    $scope.createAdmin = function () {
      var modalInstance = $modal.open({
        templateUrl: 'newAdminModalContent.html',
        controller: 'newAdminModalCtrl',
        size: 'lg'
      });
      modalInstance.result.then(function (data) {
        newAdminRequest(data);
      }, function () {
      });
    };
    $scope.editAdmin = function (admin) {

      var request = $http({
        method: 'put',
        url: '/admin/' + $scope.selected[0].id + '/edit',
        data: angular.toJson(admin)
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin edited successfully', true);
          location.reload();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    function newAdminRequest(data) {
      var request = $http({
        method: 'post',
        url: '/admin/users',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin created successfully', true);
          generateTable();
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
    function generateTable() {
      var request = $http.get('/admin/users');
      request.success(function (data) {
        if (data.success) {
          $scope.admins = data.data;
        }
      });
    }
  }
]).controller('newAdminModalCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.ok = function (data) {
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('editAdminModalCtrl', [
  '$scope',
  '$modalInstance',
  'current',
  function ($scope, $modalInstance, current) {

    $scope.current = current;
    $scope.ok = function (admin) {
      $modalInstance.close(admin);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);