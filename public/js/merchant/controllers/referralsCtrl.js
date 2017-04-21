/**
 * Referrals Ctrl
 */
app
  .controller('ReferralsCtrl', [
    '$scope',
    '$http',
    'alertsFactory',
    'transformRequestAsFormPost',
    '$modal',
    function($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      var fetchReferrals = function() {
        var request = $http({
          method: 'get',
          url: '/referrals',
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.referrals = data.data;
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.switchMerchant = function(merchantId) {
        var request = $http.get('/settings/merchants/switch/' + merchantId);
        request
          .success(function(data) {
            if (data.success) {
              location.reload();
            } else {
              $scope.alerts.addAlert('danger', null, true);
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.createMerchant = function(merchant) {
        var request = $http({
          method: 'post',
          url: '/submerchants',
          data: merchant,
        });

        request
          .success(function(data) {
            $scope.alerts.resetAlerts();
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant was created successfully'
              );
              fetchReferrals();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.createUser = function(input) {
        var request = $http({
          method: 'post',
          url: '/subusers',
          data: input,
        });

        request
          .success(function(data) {
            $scope.alerts.resetAlerts();
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'User was created successfully'
              );
              fetchReferrals();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      fetchReferrals();

      $scope.openCreateMerchant = function() {
        var modalInstance = $modal.open({
          templateUrl: 'createMerchantModal.html',
          controller: 'createMerchantCtrl',
        });
        modalInstance.result.then($scope.createMerchant, $.noop);
      };

      $scope.openCreateUserModal = function(merchant) {
        var modalInstance = $modal.open({
          templateUrl: 'createUserModal.html',
          controller: 'createUserModalCtrl',
        });
        modalInstance.merchant = merchant;
        modalInstance.result.then(
          function(password) {
            merchant.password = password.password;
            merchant.password_confirmation = password.password_confirmation;
            $scope.createUser(merchant);
          },
          function() {}
        );
      };
    },
  ])
  .controller('createMerchantCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.merchant = {
        name: '',
        email: '',
      };
      $scope.ok = function(merchant) {
        $modalInstance.close(merchant);
      };

      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('createUserModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.merchant = $modalInstance.merchant;
      $scope.ok = function(password, password_confirmation) {
        $modalInstance.close({
          password: password,
          password_confirmation: password_confirmation,
        });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
