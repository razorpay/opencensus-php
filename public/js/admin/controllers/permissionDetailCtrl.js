app.controller('PermissionDetailCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  function ($scope, $http, alertsFactory) {
    $scope.save = function(permission) {
      var data = {};

      data.body = permission;
      data.route_name = 'permission_create';

      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: data
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Permission added successfully.', true);
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
    }
  }
])
