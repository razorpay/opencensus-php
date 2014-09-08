//Admin profile Controller
app.controller('AdminCtrl', ['$scope', '$http', '$state', 'admin', 'CSRF_TOKEN', '$modal', 'alertsFactory', '$idle', '$keepalive',
  function($scope, $http, $state, admin, CSRF_TOKEN, $modal, alertsFactory, $idle, $keepalive) {
    
    admin.identity().then(function(data){
      $scope.admin = data;
    });

    $scope.alerts = alertsFactory.getHandler();

    $scope.logout = function() {
      logoutRequest()
        .finally(function() {
                $state.go('access.signin');  
        });
    };

    $scope.changePassword = function () {
      var modalInstance = $modal.open({
        templateUrl: 'passwordModalContent.html',
        controller: passwordModalCtrl
      });

      modalInstance.result.then(
        function (data) {
          passwordChangeRequest(data);
        },
        function () {
          ;
        });
    };

    $scope.$on('$idleStart', function() {
      closeModals();

      $scope.warning = $modal.open({
        templateUrl: 'warning-dialog.html',
        windowClass: 'modal-danger'
      });
    });

    $scope.$on('$idleEnd', function() {
      closeModals();
    });

    $scope.$on('$idleTimeout', function() {
      logoutRequest().finally(function(){
        $state.go('lockme', { "username": $scope.admin.username}).finally(function(){
          closeModals();
        });
      });
    });

    function logoutRequest(){
      $scope.data = {
        _token: CSRF_TOKEN
      }

      var request = $http({
          method: "get",
          url: "/admin/user/logout",
          data: $scope.data
      });

      request
        .finally(function() {
              admin.identity(true);
        });

      return request;
    };

    var passwordModalCtrl = function ($scope, $modalInstance) {
      $scope.ok = function (data) {
        $modalInstance.close(data);
      };
      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };

    function passwordChangeRequest(data) {
      $scope.alerts.addAlert('info', 'Processing...', true);

      data._token = CSRF_TOKEN;

      var request = $http({
        method: "post",
        url: "/admin/password",
        data: data
      });

      request
      .success(function(data){
        if(data.success){
          $scope.alerts.addAlert('success', 'Password changed successfully.', true);
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

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
}]);