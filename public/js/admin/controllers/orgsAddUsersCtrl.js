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
    $scope.select_all = false;
    $scope.role = '';

    $scope.roles = organization.fetchRoles();
    $scope.groups = organization.fetchGroups();

    $scope.selectAll = function() {
      $scope.selected_groups = {};

      if ($scope.select_all) {
        return;
      }

      $scope.groups.map(function(group){
        $scope.selected_groups[group.code] = true;
      });
    }


    $scope.addAdminUser = function(user) {
      var body = user;
      body.groups = [];
      body.roles = [];
      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {

          if ($scope.selected_groups[key]) {
            body.groups.push(key);
          }
        }
      }

      if ($scope.role) {
        body.roles.push($scope.role);
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