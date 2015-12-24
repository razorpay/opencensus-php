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

    $scope.roles = ['owner','manager','operations','finance','developer'];
    
    $scope.team = {
      role: $scope.roles[1]
    };

    $scope.getTeamMembers = function(){
      var request = $http.get('/settings/merchants/owned');
      request
      .success(function (data) {
        if (data.success) {
          $scope.users = data.data.users;
          $scope.invitations = data.data.invitations;
        } 
      })
      .error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    $scope.updateTeamMember = function (user){
      var request = $http({
        method: 'put',
        url: '/settings/merchants/owned/members/' + user.id ,
        data: { role: user.pivot.role }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', "Team member's role has been chnaged successfully", true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('danger', "There was an error in changing the team member's role");
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
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
          $scope.team.role = $scope.roles[1];
          $scope.team.email = '';
          $scope.getTeamMembers();
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