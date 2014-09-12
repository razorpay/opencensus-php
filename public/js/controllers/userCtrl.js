//User profile Controller
app.controller('UserCtrl', ['$scope', '$http', '$state', 'user', 'CSRF_TOKEN', '$modal', 'alertsFactory', '$idle', '$keepalive',
  function($scope, $http, $state, user, CSRF_TOKEN, $modal, alertsFactory, $idle, $keepalive) {
    
    user.identity().then(function(data){
      $scope.user = data;
    });

    $scope.alerts = alertsFactory.getHandler();

    $scope.activated = function(activated) {
      if(activated == 1) {
        return "Activated";
      }
      else {
        return "Not Activated";
      }
    };

    $scope.logout = function() {
      logoutRequest()
        .finally(function() {
                $state.go('access.signin');  
            });
    };

    $scope.changePassword = function () {
      var modalInstance = $modal.open({
        templateUrl: 'passwordModalContent.html',
        controller: 'passwordModalCtrl'
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
        $state.go('lockme', { "email": $scope.user.email}).finally(function(){
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
          url: "/user/logout",
          data: $scope.data
      });

      request
        .finally(function() {
              user.identity(true);
        });

      return request;
    };

    function passwordChangeRequest(data) {
      $scope.alerts.addAlert('info', 'Processing...', true);

      data._token = CSRF_TOKEN;

      var request = $http({
        method: "post",
        url: "/password",
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

}])
.controller('passwordModalCtrl', ['$scope', '$modalInstance',
  function($scope, $modalInstance){
    $scope.ok = function (data) {
        $modalInstance.close(data);
      };
      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}]);