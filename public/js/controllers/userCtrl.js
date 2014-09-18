//User profile Controller
app.controller('UserCtrl', ['$scope', '$http', '$state', 'user', 'CSRF_TOKEN', '$modal', 'alertsFactory', '$idle', '$keepalive',
  function($scope, $http, $state, user, CSRF_TOKEN, $modal, alertsFactory, $idle, $keepalive) {
    
    user.identity().then(function(data){
      $scope.user = data;
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
    
    $scope.$on('$keepalive', function() {
        $http({
          method: "get",
          url: "/user/keepalive",
          notBusy: true
        })
        .success(function(data){
          if(data.success == false) {
            location.reload();
          }
        })
        .error(function(){
          if($scope.connectModal) return;

          var connectModalInstance = $modal.open({
              controller: ['$scope', '$modalInstance',
                function ($scope, $modalInstance) {
                  $scope.ok = function () {
                    $modalInstance.close();
                  }
              }],
              template: '<div class="modal-header">' +
                  '<h3 class="modal-title">Alert</h3>' +
                '</div>' +
                '<div class="confirm-modal modal-body">' +
                    '<h4>Can not communicate with the server!<br/>Please check your connection and refresh the page.</h4>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button class="btn btn-primary confirm-ok" ng-click="ok()">OK</button>' +
                '</div>'
          });

          $scope.connectModal = true;
          
          connectModalInstance.result.finally(function () {
              $scope.connectModal = false;
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