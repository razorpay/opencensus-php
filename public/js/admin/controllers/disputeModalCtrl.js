'use strict';
/**
 * Single Payment Details controller
 * Child of TransactionDetailCtrl
 */
app.controller('DisputeModalCtrl', [
  '$scope',
  '$http',
  'dateFactory',
  '$modalInstance',
  'current',
  'mode',
  function($scope, $http, dateFactory, $modalInstance, current, mode) {
    $scope.currency = current.currency || 'INR';

    if (current.entity && current.entity === 'dispute') {
      $scope.editMode = true;
      $scope.dispute = current;

      $scope.dispute.expires_on *= 1000;
      $scope.dispute.raised_on *= 1000;
      $scope.dispute.amount /= 100;
    } else {
      $scope.editMode = current.disputed;
      $scope.dispute = {};
    }

    // Date options for Raised date
    $scope.dateRaised = dateFactory.getHandler($scope);
    $scope.dateRaised.dateOptions['showWeeks'] = false;
    $scope.dateRaised.dateOptions['minDate'] = moment().subtract(2, 'years'); // Avoid selection of date before 2 years back
    $scope.dateRaised.dateOptions['maxDate'] = moment(); // Avoid selection of date after today

    // Date options for Expire date
    $scope.date = dateFactory.getHandler($scope);
    $scope.date.dateOptions['showWeeks'] = false;
    $scope.date.dateOptions['minDate'] = moment().subtract(1, 'months'); // Avoid selection of date before today (Adding 1 month back temporarily)

    // Get offers of merchant to display in the list
    function getReasonId() {
      var data = {
        route_name: 'admin_fetch_entity_multiple',
        url_params: {
          '{type}': 'dispute_reason',
        },
        mode: mode,
      };
      var request = $http.get('/admin/generic', {
        params: data,
      });

      request
        .success(function(data) {
          if (data.success || true) {
            $scope.reasonIds = data.data.items;

            var reasonInfo = '';
            angular.forEach($scope.reasonIds, function(reason, index) {
              reasonInfo =
                reasonInfo + (index + 1) + '. ' + reason.description + '<br />';
            });

            $scope.reasonIdInfo = '<pre>' + reasonInfo + '</pre>';
          } else {
            $scope.alerts.resetAlerts(true);
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.resetAlerts(true);
          $scope.alerts.addAlert('danger', null);
        });
    }

    getReasonId();

    function cleanFields() {
      $scope.dispute.raised_on =
        new Date($scope.dispute.raised_on).getTime() / 1000;
      $scope.dispute.expires_on =
        new Date($scope.dispute.expires_on).getTime() / 1000;
      $scope.dispute.amount = $scope.dispute.amount * 100;
      $scope.dispute.deduct_at_onset = $scope.dispute.deduct_at_onset ? 1 : 0;

      // Pruning as Edit mode needs only 3 fields in req payload
      if ($scope.editMode) {
        var editModeDisputeFields = {};
        if ($scope.dispute.expires_on) {
          editModeDisputeFields.expires_on = $scope.dispute.expires_on;
        }
        if ($scope.dispute.gateway_dispute_status) {
          editModeDisputeFields.gateway_dispute_status =
            $scope.dispute.gateway_dispute_status;
        }
        if ($scope.dispute.status) {
          editModeDisputeFields.status = $scope.dispute.status;
        }

        $scope.dispute = editModeDisputeFields;
      }
    }

    $scope.ok = function() {
      cleanFields();
      $modalInstance.close($scope.dispute);
    };
    $scope.cancel = function() {
      $modalInstance.dismiss('cancel');
    };
  },
]);
