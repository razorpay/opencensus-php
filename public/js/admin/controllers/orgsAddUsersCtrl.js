//Merchant List controller
app.controller('OrgsAddUsersCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {
    $scope.user = {};
    $scope.selected_groups = [];

    $scope.roles = organization.fetchRoles('org_6dLbNSpv5XbCOG');
    $scope.groups = organization.fetchGroups('org_6dLbNSpv5XbCOF');

    $scope.addAdminUser = function(user) {
      var body = user;
      body.groups = [];
      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            body.groups.push(key);
          }
        }
      }

      var request = $http.post('admin/generic/', {
        route_name: 'admin_create',
        data: {
          body: body
        }
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