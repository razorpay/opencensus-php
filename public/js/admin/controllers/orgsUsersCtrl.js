//Admin List controller
app.controller('OrgsUsersCtrl', [
  '$scope',
  '$http',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  'organization',
  'admin',
  function ($scope, $http, $modal, alertsFactory, transformRequestAsFormPost,
    organization, admin) {
    admin.identity().then(function (data) {
      $scope.admin = data;
    });
    $scope.users = [];
    $scope.count = 0;
    $scope.alerts = alertsFactory.getHandler();
    $scope.groups = organization.fetchGroups();

    organization.fetchRoles().then(function(roles) {
      $scope.roles = {};

      angular.forEach(roles, function (role) {
        $scope.roles[role.id] = role.name;
      });
    });

    // Get dynamic user fields
    var request = $http({
      url: '/admin/generic',

      method: 'GET',

      params: {
        route_name: 'org_fieldmap_get',

        url_params: {
          '{entity}' : 'admin'
        }
      }
    });

    request.success(function (data) {
      if (data.success) {
        $scope.fields = data.data.fields;
      }
    });

    /**
     *  Actions
    **/

    $scope.listUsers = function() {
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

    $scope.listUsers();

    $scope.deleteOrgUser = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'admin_delete',

          url_params: {
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
          $scope.alerts.addAlert('success', 'Admin deleted successfully', true);
        }
      });
    }
  }
]);
