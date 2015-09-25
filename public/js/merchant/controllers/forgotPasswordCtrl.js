//Forgot Password Controller
app.controller('ForgotPasswordCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.data = {};
    $scope.submit = function () {
      var request = $http({
        method: 'post',
        url: '/user/password/reset',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Reset request sent. Please check your inbox for verification email from Razorpay.', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]);