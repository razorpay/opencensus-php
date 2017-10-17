'use strict';
//User profile Controller
app
  .controller('UserCtrl', [
    '$scope',
    '$http',
    '$state',
    'user',
    '$modal',
    'alertsFactory',
    '$idle',
    '$keepalive',
    'modeFactory',
    'transformRequestAsFormPost',
    '$cookies',
    'jqTourbusService',
    'organization',
    function(
      $scope,
      $http,
      $state,
      user,
      $modal,
      alertsFactory,
      $idle,
      $keepalive,
      modeFactory,
      transformRequestAsFormPost,
      $cookies,
      jqTourbusService,
      organization
    ) {
      $scope.mode = modeFactory.getMode();
      $scope.invitations = [];
      $scope.logo_full = '';

      $scope.getPendingInvitations = function() {
        var request = $http.get('/settings/invitations');
        request
          .success(function(data) {
            if (data.success) {
              $scope.invitations = data.data;
            } else {
              $scope.alerts.addAlert('danger', null, true);
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.fetchBankAccount = function() {
        var request = $http.get('/bank_account');
        request
          .success(function(data) {
            if (data.success) {
              $scope.bankAccount = data.data;
            } else {
              $scope.bankAccount = false;
            }
          })
          .error(function() {
            $scope.bankAccount = false;
          });
      };

      $scope.acceptInvitation = function(invite) {
        var request = $http.post(
          'settings/invitations/' + invite.id + '/accept'
        );
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'You have accepted the invite.',
                true
              );
              location.reload();
            } else {
              $scope.alerts.addAlert('danger', null, true);
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.rejectInvitation = function(invite) {
        var request = $http.delete(
          'settings/invitations/' + invite.id + '/reject'
        );
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'You have rejected the invite.',
                true
              );
              $scope.getPendingInvitations();
            } else {
              $scope.alerts.addAlert('danger', null, true);
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.upgradeAcount = function(business_name) {
        var request = $http({
          method: 'post',
          url: '/merchants/register',
          transformRequest: transformRequestAsFormPost,
          data: {
            business_name: business_name,
          },
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant Account Created',
                true
              );
              $state.reload();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.refreshUser = function(force) {
        user.identity(force).then(function(data) {
          $scope.user = data;
          $scope.merchantCount = Object.keys(data.merchants).length;
          $scope.loggedInUser = data.user;
          $scope.hasMerchant = false;

          if (
            $cookies.show_rzp_welcome_guide &&
            ['owner', 'manager', 'admin'].indexOf($scope.role) !== -1
          ) {
            setTimeout(function() {
              jqTourbusService.start();
            }, 1500);
            delete $cookies.show_rzp_welcome_guide;
          }

          // Does the user have an associated merchant account
          for (var i in data.user.merchants) {
            var merchant = data.user.merchants[i];
            if (
              merchant.email.toLowerCase() === data.user.email.toLowerCase()
            ) {
              $scope.hasMerchant = true;
            }
          }

          if (window.ga) {
            ga('set', 'userId', data.id);
          }
        });
      };
      $scope.refreshUser();
      $scope.alerts = alertsFactory.getHandler();
      $scope.logout = function() {
        logoutRequest().finally(function() {
          $state.go('access.signin');
        });
      };
      $scope.changePassword = function() {
        var modalInstance = $modal.open({
          templateUrl: 'passwordModalContent.html',
          controller: 'passwordModalCtrl',
        });
        modalInstance.result.then(
          function(data) {
            passwordChangeRequest(data);
          },
          function() {}
        );
      };
      $scope.$on('$idleStart', function() {
        closeModals();
        $scope.warning = $modal.open({
          templateUrl: 'warning-dialog.html',
          windowClass: 'modal-danger',
        });
      });
      $scope.$on('$idleEnd', function() {
        closeModals();
      });
      $scope.$on('$idleTimeout', function() {
        logoutRequest().finally(function() {
          $state
            .go('access.lockme', { email: $scope.user.user.email })
            .finally(function() {
              closeModals();
            });
        });
      });
      $scope.$on('$keepalive', function() {
        $http({
          method: 'get',
          url: '/user/keepalive',
          notBusy: true,
        })
          .success(function(data) {
            if (data.success === false) {
              location.reload();
            }
          })
          .error(function() {
            if ($scope.connectModal) return;
            var connectModalInstance = $modal.open({
              controller: [
                '$scope',
                '$modalInstance',
                function($scope, $modalInstance) {
                  $scope.ok = function() {
                    $modalInstance.close();
                  };
                },
              ],
              template:
                '<div class="modal-header">' +
                '<h3 class="modal-title">Alert</h3>' +
                '</div>' +
                '<div class="confirm-modal modal-body">' +
                '<h4>Can not communicate with the server!<br/>Please check your connection and refresh the page.</h4>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<button class="btn btn-primary confirm-ok" ng-click="ok()">OK</button>' +
                '</div>',
            });
            $scope.connectModal = true;
            connectModalInstance.result.finally(function() {
              $scope.connectModal = false;
            });
          });
      });
      function logoutRequest() {
        var request = $http({
          method: 'get',
          url: '/user/logout',
        });
        request.finally(function() {
          user.identity(true);
        });
        return request;
      }
      function passwordChangeRequest(data) {
        var request = $http({
          method: 'post',
          url: '/password',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Password changed successfully.',
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      }
      function closeModals() {
        if ($scope.warning) {
          $scope.warning.close();
          $scope.warning = null;
        }
        if ($scope.timedout) {
          $scope.timedout.close();
          $scope.timedout = null;
        }
      }

      // Show correct logo according to the organization
      organization.fetchCurrentOrg().then(function(data) {
        $scope.logo_full = data.main_logo_url || 'img/logo_full.png';
      });
    },
  ])
  .controller('passwordModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.ok = function(data) {
        $modalInstance.close(data);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
