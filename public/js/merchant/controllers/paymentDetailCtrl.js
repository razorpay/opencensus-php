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
  'getStateMerchant',
  function ($scope, $http, $stateParams, $modal, alertsFactory,
      transformRequestAsFormPost, getStatusClass, user, displayClass, displayValue, getState, getType, getStateMerchant) {

    $scope.tags = [];

    $scope.card = null;
    $scope.showCardDetails = false;

    $scope.displayValue = displayValue;
    $scope.displayClass = displayClass;
    $scope.getState = getState;
    $scope.getType = getType;
    $scope.getStateMerchant = getStateMerchant;

    // Keys currently added the to good-looking view
    // Or ones we do not want to show (entity, id, refunds)
    var shownByDefault = [
      'amount',
      'amount_refunded',
      'bank',
      'captured',
      'contact',
      'created_at',
      'currency',
      'description',
      'email',
      'entity',
      'error_code',
      'error_description',
      'fee',
      'id',
      'international',
      'method',
      'notes',
      'refund_status',
      'refunds',
      'service_tax',
      'status',
      'wallet'
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
          },
          currency: function () {
            return $scope.entity.currency;
          }
        }
      });
      modalInstance.result.then(function (data) {
        $scope.refund(data);
      }, $.noop);
    };

    $scope.fetchAndShowCardDetails = function() {
      var params = {};
      params.route_name = 'payment_fetch_card_details';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id
      };

      var request = $http.get('/user/generic', {
        params: params
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
          },
          currency: function () {
            return $scope.entity.currency;
          }
        }
      });
      modalInstance.result.then(function (amount) {
        $scope.capture(amount, $scope.entity.currency);
      }, function () {
      });
    };
    $scope.capture = function (amount, currency) {
      var captureAmount = parseInt(amount);
      if (!captureAmount) {
        $scope.alerts.addAlert('danger', 'Invalid capture amount', true);
        return;
      }
      var data = {
        amount: captureAmount,
        currency: currency
      };

      var params = {};
      params.route_name = 'payment_capture';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id
      };
      params.body = data;

      var request = $http({
        url: '/user/generic',
        method: 'post',
        data: params
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

      var params = {};
      params.route_name = 'payment_refund';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id
      };
      params.body = data;

      var request = $http({
        url: '/user/generic',
        method: 'post',
        data: params
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

      var params = {};
      params.route_name = 'payment_fetch_refunds';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': $scope.entity.id
      };

      var request = $http.get('/user/generic', {
        params: params
      });

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity.refunds = data.data.items;
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
  'currency',
  function ($scope, $modalInstance, amount, currency) {

    $scope.amount = amount;
    $scope.currency = currency;

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
  'currency',
  '$filter',
  function ($scope, $modalInstance, amount, currency, $filter) {

    // This is displayed with 2 decimal places
    $scope.amount = $filter('propercurrency')(amount/100, '');
    $scope.currency = currency;
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
      // TODO: Change this so we do integers everywhere.
      // Convert it back to a float from String
      amount = parseFloat(amount.replace(/[, ]/, ''));
      // We get amount with 2 decimal places
      // Note: If we ever do a currency with more or less than 2 decimal places
      // refunds will break
      var data = {
        amount: amount * 100
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
