//Merchant List controller
app.controller('MerchantInvitationCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  '$state',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, $state) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.merchant = {};

    /**
     * Actions
     */

    // Get the dynamic fields

    var request = $http({
      url: '/admin/generic',

      method: 'GET',

      params: {
        route_name: 'org_fieldmap_get',

        url_params: {
          '{entity}' : 'admin_lead'
        }
      }
    });

    request.success(function (data) {
      if (data.success) {
        $scope.fields = data.data.fields;

        if (data.data.fields.indexOf('promo_code') !== -1) {
          $scope.merchant.promo_code = 'RP_StartUP';
        }

        if (data.data.fields.indexOf('merchant_type') !== -1) {
          $scope.merchant.merchant_type = 'stp';
        }
      }
    });

    $scope.inviteMerchant = function(merchant) {

      var request = $http({
        url: '/admin/generic',

        method: 'POST',

        params: {
          route_name: 'admin_lead_create'
        },

        data: {
          body: merchant
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Invitation has been sent to ' +
            merchant.contact_email , true);

            // Redirect to invitations page
            $state.go('app.invitations.list');
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

      return request;
    }
  }
])
