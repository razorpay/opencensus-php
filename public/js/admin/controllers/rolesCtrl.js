//Merchant List controller
app.controller('RolesCtrl', [
  '$scope',
  '$http',
  '$modal',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, transformRequestAsFormPost) {
    $scope.roles = [];
    $scope.count = 0;

    $scope.regenerate = function () {
      $scope.roles.push({
        id          : '6dLbNSpv5XbCOD',
        name        : 'test_role',
        description : 'Role is in test'
      });
      $scope.count = 1;
    };

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
      // TODO Mocked new role. Remove later
      $scope.roles.push({
        id: '6dLbNSpv5XbCOD',
        name: role.name,
        description: role.description
      });
      $scope.count += 1;
      var request = $http({
        url: '/orgs/jdhsakd/roles',
        method: 'POST',
        transformRequest: transformRequestAsFormPost,
        data: {
          name: role.name,
          description: role.description
        }
      });
      request.success(function (data) {
        console.log(data.data);
      }).error(function () {
        console.log('Add Role Request failed');
      })
    };

    $scope.regenerate();
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
