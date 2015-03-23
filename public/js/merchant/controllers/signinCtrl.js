  //Signin Controller
  app.controller('SigninCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'user', 'transformRequestAsFormPost', 
    function($scope, $http, $state, $stateParams, alertsFactory, user, transformRequestAsFormPost) {
      $scope.data = {};

      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      if($stateParams.email){
        $scope.data.email = $stateParams.email;
      }

      $scope.submit = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
          return false;     
        }

        $scope.alerts.resetAlerts();

        var request = $http({
                    method: "post",
                    url: "/user/signin",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  user.identity(true);
                  $state.go('app.dashboard');
                }
                else {
                  $scope.alerts.resetAlerts();
                  
                  if(data.errors[0] == "not activated") {
                    $scope.notactivated = true;
                  }
                  else {
                    angular.forEach(data.errors, function(error, key) {
                    $scope.alerts.addAlert('danger', error);
                    }); 
                  }
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger', null, true);
              })
      };

      $scope.resend = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
          return false;     
        }

        $scope.notactivated = false;
        
        $scope.alerts.resetAlerts();
        
        var request = $http({
                    method: "post",
                    url: "/user/resend",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  $scope.alerts.addAlert('success', 'Confirmation mail re-sent, please check your inbox.', true);
                }
                else {
                  $scope.alerts.resetAlerts();
                  angular.forEach(data.errors, function(error, key) {
                  $scope.alerts.addAlert('danger', error);
                  });      
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger', null, true);
              })
      };
}]);