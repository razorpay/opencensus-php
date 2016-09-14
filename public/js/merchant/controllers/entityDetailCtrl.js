"use strict";
//Single Entity Details controller
app.controller('EntityDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  'statusClass',
  function ($scope, $http, $stateParams, alertsFactory, getStatusClass) {
    $scope.getStatusClass = getStatusClass;
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity = { id: $stateParams.id };
    $scope.generate = function (entity) {
      $scope.entity_type = entity; // because entity gets overriden after XHR below
      $scope.entity.type = entity;
      fetchEntity();
    };

    $scope.getTooltip = function(label) {
      if (label === 'admin_comment') {
        return 'This refund was initiated by Razorpay. Kindly get in touch with support@razorpay.com for more details.';
      }
      return null;
    }

    function fetchEntity() {
      var request = $http.get('/' + $scope.mode + '/' + $scope.entity.type + 's/' + $scope.entity.id);
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity = data.data.items[0];
        } else {
          angular.forEach(data.errors, function (error) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    // For settlement breakup
    $scope.showSettlementDetails = function () {
      if ($scope.isSettlementDetailsCollapsed === false) {
        $scope.isSettlementDetailsCollapsed = true;
        return;
      }

      var request = $http.get('/' + $scope.mode + '/' + $scope.entity_type + 's/' + $scope.entity.id + '/details');
      request.success(function (data) {
        if (data.success) {
          $scope.isSettlementDetailsCollapsed = false;

          $scope.breakupDetails = data.data.items;
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]);
