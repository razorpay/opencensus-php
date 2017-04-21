//Forgot Password Controller
app.controller('ForgotPasswordCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  'organization',
  function(
    $scope,
    $http,
    alertsFactory,
    transformRequestAsFormPost,
    organization
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.data = {};
    organization.fetchCurrentOrg().then(function(data) {
      $scope.logo_url = data.main_logo_url || 'img/logo_black.png';
    });

    $scope.submit = function() {
      var request = $http({
        method: 'post',
        url: '/admin/password/reset',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data,
      });
      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Reset request sent. Please check your inbox for verification email.',
              true
            );
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(error, key) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };
  },
]);
