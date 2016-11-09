//Merchant List controller
app.controller('MerchantInvitationCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.merchant = {
      promo_code: 'RP_StartUP'
    };
    /**
     * Actions
     */

    $scope.inviteMerchant = function(merchant) {
      var request = $http({
        method: 'post',
        url: '/merchants/invite',
        transformRequest: transformRequestAsFormPost,
        data: merchant
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Invitation has been sent to ' +
            merchant.email , true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
])