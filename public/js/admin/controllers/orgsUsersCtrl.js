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
      modalInstance.result.then(function (admin) {
        $scope.editAdmin(admin);
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

    $scope.listUsers('org_6dLbNSpv5XbCOG');



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

    $scope.user = current;
    $scope.ok = function (user) {
      $modalInstance.close(user);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);