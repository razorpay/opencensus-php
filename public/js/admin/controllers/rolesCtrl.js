//Merchant List controller
app.controller('RolesCtrl', [
  '$scope',
  '$http',
  '$modal',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, transformRequestAsFormPost) {
    $scope.roles = [];
    $scope.count = 0;


    /* TODO: use fetchRoles from factory */
    $scope.getRoles = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'role_get_multiple',

          url_params: {
            '{id}': 'org_6dLbNSpv5XbCOG'
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.roles = data.data.items
          $scope.count = data.data.count;
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

    };

    $scope.getRoles();

    $scope.openCreateRoleModal = function () {
      var role = {};
      var modalInstance = $modal.open({
        templateUrl: 'createRoleModal.html',
        controller: 'createRoleCtrl',
        resolve: {
          current: function () {
            return role;
          }
        }
      });
      modalInstance.result.then(function (role) {
        $scope.addRole(role);
      }, $.noop);
    };

    $scope.addRole = function (role) {
      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'role_create',
          url_params: {
            '{id}': 'org_6dLbNSpv5XbCOG'
          }
        },
        data: {
          body: {
            name: role.name,
            description: role.description
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.roles.push({
            id: data.data.id,
            name: data.data.name,
            description: data.data.description
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]).controller('createRoleCtrl', [
  '$scope',
  '$modalInstance',
  'current',
  function ($scope, $modalInstance, current) {
    $scope.role = current;

    $scope.ok = function (role) {
      $modalInstance.close(role);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel')
    };
  }
]);
