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
  'isHostedInBB',
  'appHost',
  function (
    $scope,
    $http,
    $state,
    $location,
    $stateParams,
    alertsFactory,
    transformRequestAsFormPost,
    organization,
    isHostedInBB,
    appHost,
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.success = false;
    $scope.data = {
      token: $location.search().token || $stateParams.token,
      expiryTime: $location.search().expiry_time,
      email: $location.search().email,
    };

    if (!$scope.data.token) {
      $state.go('access.signin');
    }

    function submitData(data, cb) {
      var request = $http({
        method: 'post',
        url: '/user/api/live/users/reset-password-token',
        data: data,
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

      if ($scope.isOrgAXIS && window.rzpQ) {
        window.rzpQ.push(
          window.rzpQ.now().onbr().success('merchant password changed', {
            source_trigger: 'reset password',
            session_id: window.session_id,
            merchant_email: $scope.data.email,
          }),
        );
      }
      $scope.alerts.resetAlerts();
      submitData($scope.data);
    };

    // Change logo
    organization.fetchCurrentOrg().then(function (data) {
      $scope.login_logo = data.login_logo_url || 'img/logo_black.png';
      $scope.isOrgAXIS = data.custom_code === 'axis';
    });

    if (isHostedInBB) {
      window.RZP &&
        window.RZP.rpcServer &&
        window.RZP.rpcServer(
          appHost,
          [
            {
              name: 'submitForm',
              hasReply: true,
              callback: function (password, passwordConfirmation, reply) {
                $scope.data.password = password;
                $scope.data.password_confirmation = passwordConfirmation;

                submitData($scope.data, reply);
              },
            },
          ],
          'reset-pwd',
        );
    }
  },
]);
