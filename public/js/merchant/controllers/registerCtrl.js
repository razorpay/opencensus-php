//Registration Controller
app.controller('RegisterCtrl', [
  '$scope',
  '$http',
  '$state',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  '$analytics',
  '$location',
  function ($scope, $http, $state, alertsFactory, user, transformRequestAsFormPost, $analytics, $location) {

    $scope.data = {};
    if($location.search().invitation)
      $scope.data.invitation = $location.search().invitation;

    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.agree = false;

    // Referrer is set only if present
    if ($location.search().ref) {
      $scope.data['ref'] = $location.search().ref;
    }

    $scope.submit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }
      if (!$scope.agree) {
        $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);
        return true;
      }
      if (window.location.hostname !== 'dashboard.razorpay.com' && window.location.hostname !== 'betadashboard.razorpay.com' && !$scope.data.captcha) {
        $scope.data.captcha = 'Faked';
      }
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/user/register',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          $analytics.eventTrack('signUp', {
            id: data.data.id,
            name: data.data.name,
            email: data.data.email
          });
          if(data.data.login) {
            user.identity(true);
            $state.go('app.dashboard');
          }
          else
            $scope.alerts.addAlert('success', 'Registration Successful. Please check your inbox for confirmation email from Razorpay.', true);
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
