//Admin profile Controller
app.controller('AdminCtrl', [
  '$scope',
  '$http',
  '$state',
  'admin',
  '$modal',
  'alertsFactory',
  '$idle',
  '$keepalive',
  function ($scope, $http, $state, admin, $modal, alertsFactory, $idle, $keepalive) {
    admin.identity().then(function (data) {
      $scope.admin = data;
      Rollbar.configure({
        payload: {
          person: {
            id: data.id,
            name: data.name,
            email: data.email,
            role: 'admin'
          }
        }
      });
      analytics.identify(data.id, {
        name: data.name,
        email: data.email,
        role: 'admin'
      });
    });
    $scope.alerts = alertsFactory.getHandler();
    $scope.changePassword = function () {
      var modalInstance = $modal.open({
        templateUrl: 'passwordModalContent.html',
        controller: 'passwordModalCtrl'
      });
      modalInstance.result.then(function (data) {
        passwordChangeRequest(data);
      }, function () {
      });
    };
    $scope.$on('$idleStart', function () {
      closeModals();
      $scope.warning = $modal.open({
        templateUrl: 'warning-dialog.html',
        windowClass: 'modal-danger'
      });
    });
    $scope.$on('$idleEnd', function () {
      closeModals();
    });
    var logoutRequest = function () {
      var request = $http({
        method: 'get',
        url: '/admin/user/logout'
      });
      request.finally(function () {
        admin.identity(true);
      });
      return request;
    };
    $scope.logout = function () {
      logoutRequest().finally(function () {
        $state.go('access.signin');
      });
    };
    $scope.$on('$keepalive', function () {
      $http({
        method: 'get',
        url: '/admin/user/keepalive',
        notBusy: true
      }).success(function (data) {
        if (data.success == false) {
          location.reload();
        }
      }).error(function () {
        if ($scope.connectModal)
          return;
        var connectModalInstance = $modal.open({
          controller: [
            '$scope',
            '$modalInstance',
            function ($scope, $modalInstance) {
              $scope.ok = function () {
                $modalInstance.close();
              };
            }
          ],
          template: '<div class="modal-header">' + '<h3 class="modal-title">Alert</h3>' + '</div>' + '<div class="confirm-modal modal-body">' + '<h4>Can not communicate with the server!<br/>Please check your connection and refresh the page.</h4>' + '</div>' + '<div class="modal-footer">' + '<button class="btn btn-primary confirm-ok" ng-click="ok()">OK</button>' + '</div>'
        });
        $scope.connectModal = true;
        connectModalInstance.result.finally(function () {
          $scope.connectModal = false;
        });
      });
    });
    $scope.showActivity = function () {
      if ($scope.activity)
      {
        $scope.activity = null;
        return;
      }
      $http({
        method: 'get',
        url: '/admin/activity',
      }).success(function (data) {
        $scope.activity = data.data
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.deleteSession = function(id) {
      $http({
        method: 'delete',
        url: '/admin/activity/'+id,
      }).success(function (data) {
        $scope.alerts.addAlert('success', 'Session deleted successfully.', true);
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.deleteAllOtherSessions = function(adminId) {
      $http({
        method: 'delete',
        url: '/admin/activity/',
      }).success(function (data) {
        $scope.alerts.addAlert('success', 'Sessions deleted successfully.', true);
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    function passwordChangeRequest(data) {
      var request = $http({
        method: 'post',
        url: '/admin/password',
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Password changed successfully.', true);
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
    function closeModals() {
      if ($scope.warning) {
        $scope.warning.close();
        $scope.warning = null;
      }
      if ($scope.timedout) {
        $scope.timedout.close();
        $scope.timedout = null;
      }
    }
  }
]).controller('passwordModalCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.ok = function (data) {
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);