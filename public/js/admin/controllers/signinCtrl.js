//Signin Controller
app.controller('SigninCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  'alertsFactory',
  'admin',
  'transformRequestAsFormPost',
  'organization',
  function ($scope, $http, $state, $stateParams, alertsFactory, admin, transformRequestAsFormPost, organization) {
    $scope.data = {};
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    if ($stateParams.username) {
      $scope.data.username = $stateParams.username;
    }
    $scope.submit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return false;
      }
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/admin/signin',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          admin.identity(true);
          $state.go('app.dashboard');
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

    // Change logo
    organization.fetchCurrentOrg().then(function (data) {
      if (data.login_logo_url) {
        $('#login_logo').attr('src', data.login_logo_url);
      }
      else {
        $('#login_logo').attr('src', 'img/logo_black.png');
      }
    });
  }
]);
