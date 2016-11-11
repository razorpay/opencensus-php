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
            '{id}': 'org_6dLbNSpv5XbCOF',
            '{groupId}': 'grp_6euDnqS4zQR4ke'  //TODO Add actual ids
          }
        }
      });
      request.success(function (data) {
        $scope.group.name = data.data.name;
        $scope.group.description = data.data.description;
      }).error(function () {
        console.log('Fetch Group Request Failed');
      });

      var requestOrgAdmins = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'admin_get_multiple',
          url_params: {
            '{id}': 'org_6dLbNSpv5XbCOG' //TODO Add actual id
          }
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

      var requestGroupAdmins = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'group_admins_get',
          url_params: {
            '{id}': 'org_6dLbNSpv5XbCOG' //TODO Add actual id
          }
        }
      });
      var groupAdmins = [];
      requestGroupAdmins.success(function (data) {
        angular.forEach(data.data.items, function (admin) {
          var groupAdmin = {};
          groupAdmin['id'] = admin.id;
          groupAdmin['name'] = admin.name;
          groupAdmin['email'] = admin.email;
          groupAdmins.push(groupAdmin);
        });
        $scope.groupAdmins = groupAdmins;
      }).error(function () {
        console.log('Fetch Group Admins Request Failed');
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
            '{id}': 'org_6dLbNSpv5XbCOF', //TODO Add actual ids
            '{groupId}': 'grp_6euDnqS4zQR4ke'
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
