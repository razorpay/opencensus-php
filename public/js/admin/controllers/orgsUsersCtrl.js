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
      var route_name = 'admin_edit';
      var data = {};
      data.body = {
        name: user.name,
        email: user.email,
        username: user.username,
        department_code: user.department_code,
        branch_code: user.branch_code,
        location_code: user.location_code,
        supervisor_code: user.supervisor_code,
        disabled: user.disabled
      };
      data.route_name = route_name;

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: route_name,

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

    $scope.deleteOrgUser = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'admin_delete',

          url_params: {
            '{id}': $scope.orgId,
            '{adminId}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          $scope.users = $scope.users.filter(function (x) {
            return x.id !== id
          });
          $scope.count = $scope.users.length;
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

    $scope.user = current;

    $scope.ok = function (user) {
      $modalInstance.close(user);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
