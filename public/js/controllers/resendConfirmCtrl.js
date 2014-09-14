//Resend Confirmation Controller
app.controller('ResendConfirmCtrl', ['$scope', '$http', '$state', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
  function($scope, $http, $state, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
    $scope.data = {
      _token: CSRF_TOKEN
    }

    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.submit = function($valid) {
      if(!$valid)  {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
        return false;     
      }

      $scope.alerts.addAlert('info', 'Processing...', true);

      var request = $http({
                  method: "post",
                  url: "/user/resend",
                  transformRequest: transformRequestAsFormPost,
                  data: $scope.data
              });

      request
            .success(function(data) {
              if(data.success) {
                $scope.alerts.addAlert('success', 'Confirmation mail sent, please check your inbox.', true);
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