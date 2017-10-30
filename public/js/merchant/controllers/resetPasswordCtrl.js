//Reset Passsword Controller
app.controller('ResetPasswordCtrl', [
  '$scope',
  '$http',
  '$state',
  '$location',
  '$stateParams',
  'alertsFactory',
  'transformRequestAsFormPost',
  'organization',
  function(
    $scope,
    $http,
    $state,
    $location,
    $stateParams,
    alertsFactory,
    transformRequestAsFormPost,
    organization
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.success = false;
    $scope.data = {
      token: $stateParams.token,
      expiryTime: $location.search().expiry_time,
      email: $location.search().email,
    };
    if (!$scope.data.token) {
      $state.go('access.signin');
    }
    $scope.submit = function($valid) {
      if (!$valid) {
        $scope.alerts.addAlert(
          'danger',
          'Please fill all the fields correctly',
          true
        );
        return true;
      }
      $scope.alerts.resetAlerts();
      var data = {
        route_name: 'user_reset_password_token',
        body: $scope.data,
      };
      var request = $http({
        method: 'post',
        url: '/guest/generic',
        data: data,
      });
      request
        .success(function(data) {
          $scope.alerts.resetAlerts();
          if (data.success) {
            $scope.success = true;
          } else {
            angular.forEach(data.errors, function(error, key) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger');
        });
    };

    // Change logo
    organization.fetchCurrentOrg().then(function(data) {
      $scope.login_logo = data.login_logo_url || 'img/logo_black.png';
    });
  },
]);
