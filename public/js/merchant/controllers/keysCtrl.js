//API keys listing and rolling controller
app.controller('KeysCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.keys = {
      items: [],
      count: 0
    };
    fetchKeys();
    $scope.generateKey = function () {
      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/key/new',
        transformRequest: transformRequestAsFormPost
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Key Generated', true);
          $scope.keys.items.push(data.data);
          $scope.keys.count = parseInt($scope.keys.count) + 1;
          $scope.openNewKey({
            id: data.data.id,
            secret: data.data.secret
          });
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.rollKey = function (data) {
      var key_id = data[0];
      var delay_roll = parseInt(data[1]);
      if (!key_id) {
        $scope.alerts.addAlert('danger', null, true);
        return;
      }
      var data = {
        id: key_id,
        delay_roll: delay_roll
      };
      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/keys',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Key Rolled', true);
          $scope.keys.items.push(data.data.new);
          $scope.keys.count = parseInt($scope.keys.count) + 1;
          $scope.openNewKey({
            id: data.data.new.id,
            secret: data.data.new.secret
          });
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.resetAlerts();
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.openRollKey = function (key_id) {
      var modalInstance = $modal.open({
        templateUrl: 'rollKeyModalContent.html',
        controller: 'rollKeyModalCtrl',
        resolve: {
          key_id: function () {
            return key_id;
          }
        }
      });
      modalInstance.result.then(function (data) {
        $scope.rollKey(data);
      }, function () {
      });
    };
    $scope.openNewKey = function (key) {
      var modalInstance = $modal.open({
        templateUrl: 'newKeyModalContent.html',
        controller: 'newKeyModalCtrl',
        backdrop: 'static',
        resolve: {
          key: function () {
            return key;
          }
        }
      });
      modalInstance.result.then(function () {
      }, function () {
      });
    };
    function fetchKeys() {
      var request = $http.get('/' + $scope.mode + '/keys');
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.keys.count = data.data.count;
          $scope.keys.items = data.data.items;
        } else {
          $scope.alerts.addAlert('danger');
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]).controller('rollKeyModalCtrl', [
  '$scope',
  '$modalInstance',
  'key_id',
  function ($scope, $modalInstance, key_id) {
    $scope.delay_roll = 1;
    $scope.ok = function (delay_roll) {
      $modalInstance.close([
        key_id,
        delay_roll
      ]);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('newKeyModalCtrl', [
  '$scope',
  '$modalInstance',
  'key',
  function ($scope, $modalInstance, key) {
    $scope.key = key;
    $scope.ok = function () {
      $modalInstance.close();
    };
  }
]);