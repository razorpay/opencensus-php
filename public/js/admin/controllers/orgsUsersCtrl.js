//Admin List controller
app.controller('OrgsUsersCtrl', [
  '$scope',
  '$http',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, alertsFactory, transformRequestAsFormPost) {
    $scope.users = {};
    $scope.alerts = alertsFactory.getHandler();

    $scope.listUsers = function() {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'admin_get_multiple'
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.users = data.data;
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