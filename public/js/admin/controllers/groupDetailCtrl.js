"use strict";

app.controller('GroupDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  '$rootScope',
  function ($scope, $http, $stateParams, alertsFactory, transformRequestAsFormPost, $modal, $rootScope) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.id = $stateParams.id;
    $scope.group = {
      id: $stateParams.id,
    };

    fetchGroup();

    function fetchGroup() {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'group_get',
          url_params: {
            '{groupId}': $scope.group.id,
          }
        }
      });
      request.success(function (data) {
        $scope.group.name = data.data.name;
        $scope.group.description = data.data.description;
        $scope.group.admins = data.data.admins;
        $scope.group.parentGroups = data.data.parents;
        $scope.group.subGroups = data.data.sub_groups;
      }).error(function () {
        console.log('Fetch Group Request Failed');
      });

      var requestOrgAdmins = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'admin_get_multiple',
        }
      });
      var orgAdmins = [];
      requestOrgAdmins.success(function (data) {
        angular.forEach(data.data.items, function (admin) {
          var orgAdmin = {};
          orgAdmin['id'] = admin.id;
          orgAdmin['name'] = admin.name;
          orgAdmin['email'] = admin.email;
          orgAdmins.push(orgAdmin);
        });
        $rootScope.orgAdmins = orgAdmins;
      }).error(function () {
        console.log('Fetch Org Admins Request Failed');
      });

    }

    $scope.openAddAdminModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addAdminModal.html',
        controller: 'addAdminCtrl',
      });
      modalInstance.result.then(function (admin) {
        $scope.addAdmin(admin);
      }, $.noop);
    };

    $scope.addAdmin = function (admin) {
      var request = $http.post('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'group_admins_create',
          url_params: {
            '{groupId}': 'grp_6euDnqS4zQR4ke' //TODO Add actual ids
          }
        },
        data: {
          body: {
            admin_ids: [admin.newAdminId]
          }
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Admin added to group successfully.', true);
        } else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        console.log('Failed to add admin to group');
      });
    }
  }
]).controller('addAdminCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.ok = function (admin) {
      $modalInstance.close(admin);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel')
    };
  }
]);
