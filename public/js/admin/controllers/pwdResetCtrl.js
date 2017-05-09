//Reset Passsword Controller
app.controller('AdminPwdResetCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  'alertsFactory',
  'transformRequestAsFormPost',
  'organization',
  function(
    $scope,
    $http,
    $state,
    $stateParams,
    alertsFactory,
    transformRequestAsFormPost,
    organization
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.success = false;
    $scope.data = { token: $stateParams.token };
    if (!$scope.data.token) {
      $state.go('access.auth.password');
    }

    organization.fetchCurrentOrg().then(function(data) {
      $scope.logo_url = data.main_logo_url || 'img/logo_black.png';
    });

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
      var request = $http({
        method: 'post',
        url: '/admin/password/reset/' + $scope.data.token,
        transformRequest: transformRequestAsFormPost,
        data: $scope.data,
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

      return request;
    };
  },
]);
