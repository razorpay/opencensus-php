//Admin List controller
app.controller('OrgsUsersCtrl', [
  '$scope',
  '$http',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, alertsFactory, transformRequestAsFormPost) {
    $scope.users = [];
    $scope.count = 0;
    $scope.alerts = alertsFactory.getHandler();
    $scope.orgId = 'org_6dLbNSpv5XbCOG';

    /**
     *  Modals
    **/

    $scope.openEditOrgUser = function (id) {
      $scope.selected = $scope.users.filter(function(x) { return x['id'] === id; });
      var modalInstance = $modal.open({
        templateUrl: 'editOrgUserModalContent.html',
        controller: 'editOrgUserModalCtrl',
        resolve: {
          current: function () {
            return jQuery.extend({}, $scope.selected[0]);
          }
        }
      });
      modalInstance.result.then(function (users) {
        $scope.editUser(users);
      }, $.noop);
    };

    /**
     *  Actions
    **/

    $scope.listUsers = function(id) {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'admin_get_multiple',

          url_params: {
            '{id}': id
          }
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.users = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.listUsers($scope.orgId);

    $scope.editUser = function(user) {
      var request = $http.put('/admin/generic', {
        params: {
          route_name: 'admin_edit',

          url_params: {
            '{id}': $scope.orgId,
            '{adminId}': user.id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          $scope.users = data.data.items;
          $scope.count = data.data.count;
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
]).controller('editOrgUserModalCtrl', [
  '$scope',
  '$modalInstance',
  'current',
  function ($scope, $modalInstance, current) {

    current.locked = !!current.locked;
    current.disabled = !!current.disabled;

    $scope.user = current

    $scope.ok = function (user) {
      $modalInstance.close(user);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);