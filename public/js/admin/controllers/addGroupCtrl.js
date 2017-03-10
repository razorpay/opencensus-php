app.controller('AddGroupCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  '$stateParams',
  '$state',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization, $stateParams, $state) {
    $scope.alerts = alertsFactory.getHandler();

    // $scope.users = organization.fetchUsers();
    $scope.group = {};
    $scope.selected_users = [];
    $scope.select_all_groups = false;
    $scope.select_all_users = false;
    $scope.selected_groups = {};

    // parent group selection from dropdown
    // if you uncomment this line then the select2
    // placeholder will stop showing up
    //
    // $scope.new_parent_group = null;

    var group_id = $stateParams.id;

    if (group_id) {
      $scope.fillParentList = function () {
        organization.fetchAllowedGroups(group_id).then(function (groups) {
          $scope.groups = groups;

          setTimeout(function () {
            $('.select2').select2({
              placeholder: 'Select a Parent Group'
            });
          }, 100);
        });
      };

      $scope.fillParentList();
    }
    else {
      organization.fetchGroups().then(function (groups) {
        $scope.groups = groups;

        setTimeout(function () {
          $('.select2').select2({
            placeholder: 'Select a Parent Group'
          });
        }, 100);
      });
    }

    if (group_id) {
      // Get group details
      $scope.getGroupDetails = function () {
        $scope.group_id = group_id;

        var request = $http({
          url: '/generic',

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
              parents: data.data.parents,
              sub_groups: data.data.sub_groups,
            };

            data.data.parents.forEach(function (group) {
              $scope.selected_groups[group.id] = true;
            });
          }
        });
      };

      $scope.getGroupDetails();
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
        $scope.selected_groups[group.id] = true;
      });
    }

    $scope.save = function (group) {
      var body = angular.extend({}, group);
      var request;

      delete body.sub_groups;

      // Reset body parents, we'll fill in values basis the selected ones from UI
      body.parents = [];

      for (var key in $scope.selected_groups) {
        if ($scope.selected_groups.hasOwnProperty(key)) {
          // Checks if value is `true`
          if ($scope.selected_groups[key]) {
            body.parents.push(key);
          }
        }
      }

      // The newly selected one from dropdown
      if ($scope.new_parent_group) {
        body.parents.push($scope.new_parent_group);
      }

      if ($scope.group_id) {
        // Edit

        request = $http({
          url: '/generic',
          method: 'PUT',
          params: {
            route_name: 'edit_group',

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

        request = $http({
          url: '/generic',
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

          // Some success tasks that needs to be done

          // 1. Reset select2
          // Seems like a bad hack, should get better with react transition
          $scope.new_parent_group = undefined;
          $('.select2').select2({
            placeholder: 'Select a Parent Group', allowClear: true
          });

          // 2. Update Parent Group list
          if ($scope.getGroupDetails) {
            $scope.getGroupDetails();
          }

          // 3. Update allowed/eligible parent list
          if ($scope.fillParentList()) {
            $scope.fillParentList();
          }
        } else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

      return request;
    }
  }
])
