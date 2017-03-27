app.controller('PermissionDetailCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  function ($scope, $http, alertsFactory, $stateParams) {
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
        }
      });
    };

    if ($stateParams.id) {
      $scope.fetchPermission($stateParams.id)
    }

    $scope.save = function (permission) {
      // edit
      if (permission.id) {
        var data = {
          route_name: 'permission_edit',
          url_params: {
            '{id}' : $scope.permission.id
          },
          body: permission
        };
        delete data.body.id;

        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data
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
          data: data
        });
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Permission saved successfully.', true);
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
