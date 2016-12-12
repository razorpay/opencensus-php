//Confirmation Controller
app.controller('ConfirmCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  'alertsFactory',
  'organization',
  function ($scope, $http, $state, $stateParams, alertsFactory, organization) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.confirm_logo = '';
    $scope.success = false;
    var token = $stateParams.token;
    if (!token) {
      $state.go('access.signin');
    }

    organization.fetchCurrentOrg().then(function (data) {
      if (data.login_logo_url) {
        $scope.confirm_logo = data.login_logo_url;
      }
      else {
        $scope.confirm_logo = 'img/logo_black.png';
      }
    });

    $scope.alerts.resetAlerts();
    var request = $http({
      method: 'get',
      url: '/user/confirm/' + token
    });
    request.success(function (data) {
      $scope.alerts.resetAlerts();
      if (data.success) {
        $scope.success = true;
      } else {
        angular.forEach(data.errors, function (error, key) {
          $scope.alerts.addAlert('danger', error);
        });
      }
    }).error(function () {
      $scope.alerts.addAlert('danger', null, true);
    });
  }
]);