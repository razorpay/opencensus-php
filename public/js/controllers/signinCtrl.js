  //Signin Controller
  app.controller('SigninCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'user', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, $state, $stateParams, alertsFactory, user, transformRequestAsFormPost, CSRF_TOKEN) {
      $scope.data = {
        _token: CSRF_TOKEN
      }

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

        $scope.alerts.addAlert('info', 'Processing...', true);

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