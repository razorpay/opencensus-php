//Signin Controller
app.controller('SigninCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  'organization',
  function ($scope, $http, $state, $stateParams, alertsFactory, user,
    transformRequestAsFormPost, organization) {
    $scope.data = {};
    $scope.login_logo = '';

    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    if ($stateParams.email) {
      $scope.data.email = $stateParams.email;
    }
    $scope.submit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return false;
      }
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/user/signin',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          user.identity(true).then(function(user) {
            console.log(user.merchants[user.id].pivot.role);
            if (user.merchants[user.id].pivot.role === 'support') {
              $state.go('app.payments.list');
            } else {
              $state.go('app.dashboard');
            }
          });
        } else {
          $scope.alerts.resetAlerts();
          if (data.errors[0] == 'not activated') {
            $scope.notactivated = true;
          } else {
            angular.forEach(data.errors, function (error, key) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.resend = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return false;
      }
      $scope.notactivated = false;
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/user/resend',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Confirmation mail re-sent, please check your inbox.', true);
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
        $scope.login_logo = data.login_logo_url
      }
      else {
        $scope.login_logo = 'img/logo_black.png';
      }
    });
  }
]);