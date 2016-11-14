//Merchant List controller
app.controller('OrgsAddUsersCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {

    $scope.roles = organization.fetchRoles('org_6dLbNSpv5XbCOG');
    $scope.groups = organization.fetchGroups('org_6dLbNSpv5XbCOF');

    $scope.user = {};

    $scope.addAdminUser = function(user) {
      var data = {};
      data.body = user;
      data.route_name = 'admin_create';
      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin created successfully.', true);
        } else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    /**
     * Actions
     */
  }
])