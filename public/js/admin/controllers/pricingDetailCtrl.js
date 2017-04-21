'use strict';
/**
 * PricingDetailCtrl
 */
app.controller('PricingDetailCtrl', [
  '$scope',
  '$stateParams',
  '$http',
  'alertsFactory',
  function($scope, $stateParams, $http, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.pricing_plan_id = $stateParams.id;

    $scope.details = {};

    $scope.fetchPlanDetails = function() {
      var params = {
        route_name: 'pricing_get_plan',
        url_params: {
          '{id}': $scope.pricing_plan_id,
        },
      };

      var request = $http.get('/admin/generic', {
        params: params,
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.details = data.data;
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

    $scope.fetchPlanDetails();
  },
]);
