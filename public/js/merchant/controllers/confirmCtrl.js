//Confirmation Controller
app.controller('ConfirmCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  '$timeout',
  'alertsFactory',
  'organization',
  'isHostedInBB',
  'appHost',
  function (
    $scope,
    $http,
    $state,
    $stateParams,
    $timeout,
    alertsFactory,
    organization,
    isHostedInBB,
    appHost,
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.success = false;

    var token = $stateParams.token;
    if (!token) {
      $state.go('access.signin');
    }

    organization.fetchCurrentOrg().then(function (data) {
      if (data.login_logo_url) {
        $scope.confirm_logo = data.login_logo_url;
      } else {
        $scope.confirm_logo = 'img/logo_black.png';
      }
    });

    $scope.alerts.resetAlerts();

    var data = {
      confirm_token: token,
    };

    var request = $http({
      method: 'put',
      url: '/user/api/live/users/confirm_user_by_data',
      data: data,
    });

    $scope.requestDone = false;

    request
      .success(function (data) {
        $scope.requestDone = true;
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.success = true;

          var dripPayload = {
            email: data.data.email,
            email_verified: true,
          };

          window.trackHubs({
            name: 'update_property',
            data: dripPayload,
          });

          if (isHostedInBB) {
            return $scope.successCb() && $scope.successCb();
          }

          $timeout(function () {
            try {
              // try-catch, since there could be tracker blocking scripts
              _dcq.push(['identify', dripPayload]);
            } catch (e) {}

            var logoutRequest = $http({
              method: 'post',
              url: '/user/logout',
            });

            logoutRequest.success(function (data) {
              location.hash = '/access/signin';
            });
          }, 3000);
        } else {
          if (isHostedInBB) {
            return $scope.failCb && $scope.failCb();
          }

          $scope.alerts.addAlert(
            'danger',
            'Invalid confirmation token or the merchant is already confirmed.',
          );
        }
      })
      .error(function () {
        $scope.requestDone = true;
        $scope.alerts.addAlert('danger', null, true);
      });

    organization.fetchCurrentOrg().then(function (data) {
      $scope.confirm_logo = data.login_logo_url || 'img/logo_black.png';
    });

    if (isHostedInBB) {
      window.RZP &&
        window.RZP.rpcServer &&
        window.RZP.rpcServer(
          appHost,
          [
            {
              name: 'notifyConfirmationSuccess',
              hasReply: true,
              callback: function (reply) {
                if ($scope.requestDone && $scope.success) {
                  return reply();
                }

                $scope.successCb = reply;
              },
            },
            {
              name: 'notifyConfirmationFail',
              hasReply: true,
              callback: function (reply) {
                if ($scope.requestDone && !$scope.success) {
                  return reply();
                }

                $scope.failCb = reply;
              },
            },
          ],
          'confirmation',
        );
    }
  },
]);
