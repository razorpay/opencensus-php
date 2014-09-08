//Registration Controller
app.controller('RegisterCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
  function($scope, $http, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
    
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.data = {
      _token: CSRF_TOKEN
    }

    $scope.agree = false;

    $scope.submit = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
          return true;     
        }

        if(!$scope.agree) {
          $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);     
          return true;
        }

        $scope.alerts.addAlert('info', 'Processing...', true);

        var request = $http({
                    method: "post",
                    url: "/user/register",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  $scope.alerts.addAlert('success', "Registration Successful. Please check your inbox for confirmation email from Razorpay.", true);
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
