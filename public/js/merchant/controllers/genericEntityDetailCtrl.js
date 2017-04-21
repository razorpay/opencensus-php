'use strict';
// Single Generic Entity Details controller
// [Currently handling Payment Details, Order Details, Refund Details, Settlement Details]
app.controller('GenericEntityDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  'statusClass',
  function($scope, $http, $stateParams, alertsFactory, getStatusClass) {
    $scope.getStatusClass = getStatusClass;
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity = { id: $stateParams.id };
    $scope.generate = function(entity) {
      $scope.entity_type = entity; // because entity gets overriden after XHR below
      $scope.entity.type = entity;
      fetchEntity();
    };

    $scope.getTooltip = function(label) {
      if (label === 'admin_comment') {
        return 'This refund was initiated by Razorpay. Kindly get in touch with support@razorpay.com for more details.';
      }
      return null;
    };

    function fetchEntity() {
      var params = {};

      var route_names = {
        payment: 'payment_fetch_by_id',
        refund: 'refund_fetch_by_id',
        order: 'order_fetch_by_id',
        settlement: 'setl_fetch_by_id',
      };

      if (route_names.hasOwnProperty($scope.entity.type)) {
        params.route_name = route_names[$scope.entity.type];
      }

      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id,
      };

      var request = $http.get('/user/generic', {
        params: params,
      });

      request
        .success(function(data) {
          $scope.alerts.resetAlerts();
          if (data.success) {
            $scope.entity = data.data;
          } else {
            angular.forEach(data.errors, function(error) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    }
  },
]);
