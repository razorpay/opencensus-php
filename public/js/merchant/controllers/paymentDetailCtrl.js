"use strict";
//Child of TransactionDetailCtrl
app.controller('PaymentDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  'statusClass',
  'user',
  'displayClass',
  'displayValue',
  'getState',
  'getType',
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost, getStatusClass, user, displayClass, displayValue, getState, getType) {

    $scope.tags = [];

    $scope.card = null;
    $scope.showCardDetails = false;

    $scope.displayValue = displayValue;
    $scope.displayClass = displayClass;
    $scope.getState = getState;
    $scope.getType = getType;

    // Keys currently added the to good-looking view
    var shownByDefault = [
      'amount',
      'amount_refunded',
      'currency',
      'status',
      'captured',
      'method',
      'bank',
      'wallet',
      'refund_status',
      'description',
      'email',
      'contact',
      'fee',
      'service_tax',
      'error_code',
      'error_description',
      'notes',
      'created_at',
      // Not much point of showing these 2 in payment view
      'entity',
      'id'
    ];

    /**
     * Returns the keys not present in the default view
     */
    $scope.keysNotShown = function() {
      var keys = [];
      for (var key in $scope.entity) {
        // If the entity has that key and its not currently shown
        if ($scope.entity.hasOwnProperty(key) && shownByDefault.indexOf(key)<0) {
          keys.push(key);
        }
      }
      return keys;
    };

    user.identity(true).then(function (data) {
      $scope.tags = data.tags;
    });

    $scope.getStatusClass = getStatusClass;

    $scope.openRefundModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'refundModalContent.html',
        controller: 'RefundModalCtrl',
        resolve: {
          amount: function () {
            return $scope.entity.amount - $scope.entity.amount_refunded;
          }
        }
      });
      modalInstance.result.then(function (data) {
        $scope.refund(data);
      }, $.noop);
    };

    $scope.fetchAndShowCardDetails = function() {
      var request = $http({
        method: 'get',
        url: '/' + $scope.mode + '/payments/' + $scope.entity.id + '/card',
      });
      request.success(function (data) {
        if (data.success) {
          $scope.card = data.data;
          $scope.showCardDetails = true;
        }
      });
    };

    $scope.toggleCardDetails = function() {
      if ($scope.card === null) {
        $scope.fetchAndShowCardDetails();
      }
      else
      {
        $scope.showCardDetails = ! $scope.showCardDetails;
      }
    };

    $scope.openCaptureModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'captureModalContent.html',
        controller: 'CaptureModalCtrl',
        resolve: {
          amount: function () {
            var baseAmount = $scope.entity.amount;

            if ($scope.tags.indexOf('Feebearer') !== -1) {
              baseAmount -= $scope.entity.fee;
            }

            return baseAmount;
          }
        }
      });
      modalInstance.result.then(function (amount) {
        $scope.capture(amount);
      }, function () {
      });
    };
    $scope.capture = function (amount) {
      var captureAmount = parseInt(amount);
      if (!captureAmount) {
        $scope.alerts.addAlert('danger', 'Invalid capture amount', true);
        return;
      }
      var data = { amount: captureAmount };
      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/payments/' + $scope.entity.id + '/capture',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Payment Captured', true);
          $scope.entity.status = 'captured';
          $scope.entity.amount = captureAmount;
        } else {
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.refund = function (data) {
      data.amount = parseInt(data.amount);
      var unrefundedAmount = parseInt($scope.entity.amount) - parseInt($scope.entity.amount_refunded);

      if (!data.amount || data.amount > unrefundedAmount) {
        $scope.alerts.addAlert('danger', 'Refund amount should be an integer and less than amount minus amount refunded.', true);
        return;
      }

      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/payments/' + $scope.entity.id + '/refund',
        data: data
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Payment Refunded', true);
          if (data.amount == unrefundedAmount)
            $scope.entity.refund_status = 'full';
          else
            $scope.entity.refund_status = 'partial';
          $scope.entity.amount_refunded = parseInt($scope.entity.amount_refunded) + data.amount;
        } else {
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.showRefunds = function () {
      if ($scope.isRefundsCollapsed === false) {
        $scope.isRefundsCollapsed = true;
        return;
      }
      var request = $http.get('/' + $scope.mode + '/payments/' + $scope.entity.id + '/refunds');
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity.refunds = data.data;
          $scope.isRefundsCollapsed = false;
        } else {
          angular.forEach(data.errors, function (error) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
])  //Capture Modal Box Controller
.controller('CaptureModalCtrl', [
  '$scope',
  '$modalInstance',
  'amount',
  function ($scope, $modalInstance, amount) {
    $scope.amount = amount;
    $scope.ok = function (amount) {
      $modalInstance.close(amount);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
])  //Refund Modal Box Controller
.controller('RefundModalCtrl', [
  '$scope',
  '$modalInstance',
  'amount',
  function ($scope, $modalInstance, amount) {

    // This is stored in INR
    $scope.amount = (amount/100).toFixed(2);
    $scope.notes = {
      comment: null
    };

    $scope.setAmount = function(isPartial) {
      if (isPartial) {
        this.refund_amount = '';
      } else {
        this.refund_amount = $scope.amount;
      }
    };

    $scope.ok = function (amount, comment) {
      // We get amount in INR
      var data = {
        amount: amount*100
      };
      if (comment !== '') {
        data.notes = {
          comment: comment
        };
      }
      $modalInstance.close(data);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

  }
]);
