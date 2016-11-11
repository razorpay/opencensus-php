//Merchant List controller
app.controller('OrgsAddRolesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {

    $scope.permissions = organization.fetchPermissions('org_6dLbNSpv5XbCOG');
    $scope.role = {};

    /**
     * Actions
     */

    $scope.save = function (role, permissions) {
      var body = role;
      // Permissions have to be saved in a separate call
      body.permissions = $scope.permissions.map(function(perm){
        return perm.id;
      });
      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'role_create'
        },
        data: {
          body: body
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Role added', true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
])
