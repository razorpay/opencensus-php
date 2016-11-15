app.controller('AddGroupCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  '$stateParams',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization, $stateParams) {

    $scope.groups = organization.fetchGroups();
    // $scope.users = organization.fetchUsers();
    $scope.group = {};
    $scope.selected_users = [];
    $scope.select_all_groups = false;
    $scope.select_all_users = false;
    $scope.selected_groups = {};

    var group_id = $stateParams.id;

    if (group_id) {
      // Get group details
      $scope.group_id = group_id;

      var request = $http({
        url: '/admin/generic',

        method: 'GET',

        params: {
          route_name: 'group_get',

          url_params: {
            '{groupId}' : group_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.group = {
            name: data.data.name,
            description: data.data.description,
          };

          data.data.groups.forEach(function (group) {
            $scope.selected_groups[group.id] = true;
          });
        }
      });
    }


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

      body.groups = [];
      body.admins = [];

      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            body.groups.push(key);
          }

        }
      }

      // for (var key in $scope.selected_users) {
      //   if ($scope.selected_users.hasOwnProperty(key)) {

      //     if ($scope.selected_users[key]) {
      //       body.admins.push(key);
      //     }

      //   }
      // }

      // var request = $http({
      //   url: '/admin/generic',
      //   method: 'POST',
      //   params: {
      //     route_name: 'group_create'
      //   },
      //   data: {
      //     body: body
      //   }
      // });

      if ($scope.group_id) {
        // Edit

        var request = $http({
          url: '/admin/generic',
          method: 'PUT',
          params: {
            route_name: 'group_edit',

            url_params: {
              '{groupId}' : $scope.group_id
            }
          },
          data: {
            body: body
          }
        });
      }
      else {
        // Create

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
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Group saved', true);
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
