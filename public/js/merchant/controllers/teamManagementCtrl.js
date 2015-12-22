//Team Management Controller
app.controller('TeamManagementCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'user',
  'uiLoad',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, user, uiLoad, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();
    
    $scope.team = {
      role: 'manager'
    };

    $scope.sendInvitation = function() {
      
      var request = $http({
        method: 'post',
        url: '/settings/invitations',
        transformRequest: transformRequestAsFormPost,
        data: $scope.team
      });
      
      request
      .success(function (data) {
        if (data.success) {
          $scope.alerts
                .addAlert('success', 'Invitation has been successfully sent to ' + $scope.team.email, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

    };
  }
]);