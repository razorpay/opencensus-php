//Confirmation Controller
app.controller('ConfirmCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  '$timeout',
  'alertsFactory',
  'organization',
  function(
    $scope,
    $http,
    $state,
    $stateParams,
    $timeout,
    alertsFactory,
    organization
  ) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.success = false;

    var token = $stateParams.token;
    if (!token) {
      $state.go('access.signin');
    }

    organization.fetchCurrentOrg().then(function(data) {
      if (data.login_logo_url) {
        $scope.confirm_logo = data.login_logo_url;
      } else {
        $scope.confirm_logo = 'img/logo_black.png';
      }
    });

    $scope.alerts.resetAlerts();

    var data = {
      route_name: 'user_confirm_by_data',
      body: {
        confirm_token: token,
      },
    };

    var request = $http({
      method: 'put',
      url: '/guest/generic',
      data: data,
    });

    request
      .success(function(data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.success = true;

          var dripPayload = {
            email: data.data.email,
            email_verified: true,
          };

          $timeout(function() {
            try {
              // try-catch, since there could be tracker blocking scripts
              _dcq.push(['identify', dripPayload]);
            } catch (e) {}

            var logoutRequest = $http({
              method: 'get',
              url: '/user/logout',
            });

            logoutRequest.success(function(data) {
              location.hash = '/access/signin';
            });
          }, 3000);
        } else {
          angular.forEach(data.errors, function(error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      })
      .error(function() {
        $scope.alerts.addAlert('danger', null, true);
      });

    organization.fetchCurrentOrg().then(function(data) {
      $scope.confirm_logo = data.login_logo_url || 'img/logo_black.png';
    });
  },
]);
