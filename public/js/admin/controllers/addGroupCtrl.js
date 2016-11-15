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
    $scope.select_all_groups = false;
    $scope.select_all_users = false;


    /**
     * Actions
     */

    $scope.selectAllGroups = function() {
      $scope.selected_groups = {};

      if (!$scope.select_all_groups) {
        return;
      }

      $scope.groups.map(function(group){
        $scope.selected_groups[group.code] = true;
      });
    }

    $scope.selectAllUsers = function() {
      $scope.selected_users = {};

      if (!$scope.select_all_users) {
        return;
      }

      $scope.users.map(function(user){
        $scope.selected_users[user.id] = true;
      });
    }

    $scope.save = function (group) {
      var body = group;

      body.sub_groups = [];
      body.admins = [];

      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            body.sub_groups.push(key);
          }

        }
      }

      for (var key in $scope.selected_users) {
        if ($scope.selected_users.hasOwnProperty(key)) {

          if ($scope.selected_users[key]) {
            body.admins.push(key);
          }

        }
      }

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'group_create'
        },
        data: {
          body: body
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Group added', true);
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
