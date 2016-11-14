app.controller('AddGroupCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {

    $scope.groups = organization.fetchGroups();
    $scope.users = organization.fetchUsers();
    $scope.group = {};
    $scope.selected_groups = [];
    $scope.selected_users = [];


    /**
     * Actions
     */

    $scope.save = function (group) {
      var body = group;

      body.groups = [];
      body.users = [];

      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            body.groups.push(key);
          }

        }
      }

      for (var key in $scope.selected_users) {
        if ($scope.selected_users.hasOwnProperty(key)) {

          if ($scope.selected_users[key]) {
            body.users.push(key);
          }

        }
      }

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
        } else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
])
