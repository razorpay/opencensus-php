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

          url_params: {
            '{id}': 'org_6dLbNSpv5XbCOF'
          }
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
      // TODO Mocked new group. Remove later
      $scope.groups.push({
        id: '6dLbNSpv5XbCOD',
        name: group.name,
        description: group.description
      });
      $scope.count += 1;
      var request = $http({
        url: '/orgs/jdhsakd/groups',
        method: 'POST',
        transformRequest: transformRequestAsFormPost,
        data: {
          name: group.name,
          description: group.description
        }
      });
      request.success(function (data) {
        console.log(data.data);
      }).error(function () {
        console.log('Add Group Request failed');
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
