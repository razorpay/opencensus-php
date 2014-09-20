  //Confirmation Controller
app.controller('ConfirmCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
  function($scope, $http, $state, $stateParams, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.success = false;

    var token = $stateParams.token;

    if(!token) {
      $state.go('access.signin');
    }

    $scope.alerts.resetAlerts();
    
    var request = $http({
                      method: "get",
                      url: "/user/confirm/"+token
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
            $scope.alerts.addAlert('danger', null, true);
          });
}]);