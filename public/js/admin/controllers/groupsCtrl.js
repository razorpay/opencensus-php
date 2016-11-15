//Merchant List controller
app.controller('GroupsCtrl', [
  '$scope',
  '$http',
  '$modal',
  'transformRequestAsFormPost',
  function ($scope, $http, $modal, transformRequestAsFormPost) {
    $scope.groups = [];
    $scope.count = 0;

    $scope.getGroups = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'group_get_multiple',
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.groups = data.data.items;
          $scope.count = data.data.count;
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.getGroups();

    $scope.openCreateGroupModal = function () {
      var group = {};
      var modalInstance = $modal.open({
        templateUrl: 'createGroupModal.html',
        controller: 'createGroupCtrl',
        resolve: {
          current: function () {
            return group;
          }
        }
      });
      modalInstance.result.then(function (group) {
        $scope.addGroup(group);
      }, $.noop);
    };

    $scope.addGroup = function (group) {
      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'group_create',
        },
        data: {
          body: {
            name: group.name,
            description: group.description
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.groups.push(data.data);
          $scope.count += 1;
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.deleteGroup = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'group_delete',

          url_params: {
            '{groupId}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          $scope.groups = $scope.groups.filter(function (x) {
            return x.id !== id
          });
          $scope.count = $scope.groups.length;
        }
      });
    };
  }
]).controller('createGroupCtrl', [
  '$scope',
  '$modalInstance',
  'current',
  function ($scope, $modalInstance, current) {
    $scope.group = current;
    $scope.ok = function (group) {
      $modalInstance.close(group);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel')
    };
  }
]);
