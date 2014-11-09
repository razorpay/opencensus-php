 //Reset Passsword Controller
app.controller('ResetPasswordCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', 
  function($scope, $http, $state, $stateParams, alertsFactory, transformRequestAsFormPost) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.success = false;

    $scope.data = {
      token: $stateParams.token
    };

    if(!$scope.data.token) {
      $state.go('access.signin');
    }
        
    $scope.submit = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields correctly', true);     
          return true;     
        }

        $scope.alerts.resetAlerts();
        
        var request = $http({
                    method: "post",
                    url: "/user/password/reset/"+$scope.data.token,
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                $scope.alerts.resetAlerts();
                if(data.success) {
                  $scope.success = true;
                }
                else {
                  angular.forEach(data.errors, function(error, key) {
                    $scope.alerts.addAlert('danger', error);
                  });      
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger');
              })
    };
}]);