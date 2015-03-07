//Registration Controller
app
.controller('RegisterCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', '$analytics',
  function($scope, $http, alertsFactory, transformRequestAsFormPost, $analytics) {
    
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.data = {};

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

        if(window.location.hostname !== "dashboard.razorpay.com" && window.location.hostname !== "betadashboard.razorpay.com" && !$scope.data.captcha)
        {
          $scope.data.captcha = "Faked";
        }

        $scope.alerts.resetAlerts();
        
        var request = $http({
                    method: "post",
                    url: "/user/register",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  $analytics.eventTrack('signUp', {id: data.data.id, name:data.data.name, email:data.data.email});
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
