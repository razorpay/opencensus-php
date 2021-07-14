//Email Update Controller
app.controller('emailUpdateCtrl', [
  '$scope',
  '$http',
  '$state',
  '$location',
  '$stateParams',
  'alertsFactory',
  'organization',
  'isHostedInBB',
  'appHost',
  function (
    $scope,
    $http,
    $state,
    $location,
    $stateParams,
    alertsFactory,
    organization,
    isHostedInBB,
    appHost,
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.success = false;
    $scope.data = {
      token: $location.search().token || $stateParams.token,
      merchant_id: $location.search().mid,
      email: $location.search().email,
    };

    if (!$scope.data.token) {
      $state.go('access.signin');
    }

    function submitData(data, cb) {
      delete data.email;
      var request = $http({
        method: 'post',
        url: 'user/api/live/merchants/email/update/create_user',
        data,
      });
      request
        .success(function (data) {
          if (cb) {
            return cb(data);
          }

          $scope.alerts.resetAlerts();
          if (data.success) {
            $scope.success = true;
          } else {
            angular.forEach(data.errors, function (error, key) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        })
        .error(function () {
          if (cb) {
            return cb({ success: false });
          }

          $scope.alerts.addAlert('danger');
        });
    }

    $scope.submit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields correctly', true);
        return true;
      }
      $scope.alerts.resetAlerts();
      submitData($scope.data);
    };

    // Change logo
    organization.fetchCurrentOrg().then(function (data) {
      $scope.login_logo = data.login_logo_url || 'img/logo_black.png';
    });
  },
]);
