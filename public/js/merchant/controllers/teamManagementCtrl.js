"use strict";
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

    $scope.roles = ['owner', 'manager', 'operations', 'finance'];

    $scope.team = {};

    $scope.roleOptions = [
      { name: 'Manager', id: 'manager' },
      { name: 'Operations', id: 'operations' },
      { name: 'Finance', id: 'finance' }
    ];

    user.identity(true).then(function(data) {
      $scope.merchant = data;
      if (data.tags.indexOf('Roles') == -1) {
        $scope.roleOptions = [
          { name: 'Manager', id: 'manager' },
        ];
      }
    });

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
    };

    $scope.updateTeamMember = function (user){
      var request = $http({
        method: 'put',
        url: '/settings/merchants/owned/members/' + user.id ,
        data: { role: user.pivot.role }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', "Team member's role has been changed successfully", true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          if(data.errors)
          {
            angular.forEach(data.errors, function (value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
          else
            $scope.alerts.addAlert('danger', "There was an error in changing the team member's role");
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.removeTeamMember = function (user){
      var request = $http({
        method: 'delete',
        url: '/settings/merchants/owned/members/' + user.id ,
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', "Team member has been removed successfully.", true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.updateInvitation = function (invite){
      var request = $http({
        method: 'put',
        url: '/settings/invitations/' + invite.id,
        data: { role: invite.role }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', "Team member's role has been changed successfully", true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.removeInvitation = function (invite){
      var request = $http({
        method: 'delete',
        url: '/settings/invitations/' + invite.id,
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', "Team member's invitation has been removed successfully", true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.resendInvitation = function(invite) {

      var request = $http.get('/settings/invitations/' + invite.id + '/resend');

      request
      .success(function (data) {
        if (data.success) {
          $scope.alerts
            .addAlert('success', 'Invitation has been successfully resent to ' + invite.email, true);
          $scope.getTeamMembers();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function () {
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
          angular.forEach(data.errors, function (value) {
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
