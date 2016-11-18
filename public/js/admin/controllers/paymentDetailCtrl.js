"use strict";
/**
 * Single Payment Details controller
 * Child of TransactionDetailCtrl
 */
app.controller('PaymentDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  'statusClass',
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost, getStatusClass) {
    $scope.getStatusClass = getStatusClass;

    // Keys currently added the to good-looking view
    var shownByDefault = [
      'amount',
      'amount_authorized',
      'amount_refunded',
      'authorized_at',
      'authorized_at',
      'bank',
      'captured_at',
      'card_id',
      'contact',
      'created_at',
      'currency',
      'customer_id',
      'description',
      'email',
      'emi_plan_id',
      'error_code',
      'error_description',
      'gateway',
      'id',
      'internal_error_code',
      'merchant_id',
      'method',
      'notes',
      'order_id',
      'refund_status',
      'refunds',
      'signed',
      'status',
      'terminal_id',
      'transaction_id',
      'updated_at',
      'verified',
      'wallet',
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

    $scope.displayClass = function (value) {
      if (value === null) {
        return 'label label-warning col-lg-1';
      } else if (value === '') {
        return 'label label-info';
      } else {
        return '';
      }
    };

    $scope.timestamp = function(value) {
      moment().zone(5.5);
      // If not null and not zero
      if (value) {
        return moment(value * 1000).format('D MMM YYYY h:mm:ss a (ddd) ') + 'IST';
      }
      else {
        return 'null';
      }
    };
    $scope.displayValue = function (value) {
      if (value === null) {
        return 'null';
      } else {
        return value;
      }
    };

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
    $scope.refundAuthorized = function () {
      var request = $http({
        method: 'post',
        url: '/admin/' + $scope.mode + '/' + $scope.entity.merchant_id + '/payments/' + $scope.entity.id + '/refund_authorized'
      });
      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data);
          $scope.alerts.addAlert('success', 'Payment Refunded Successfully: ' + payment, true);
          window.location.reload();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.authorizeFailedPayment = function () {
      var request = $http({
        method: 'post',
        url: '/admin/' + $scope.mode + '/payments/' + $scope.entity.id + '/authorize_failed'
      });
      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data.payment);
          $scope.alerts.addAlert('success', 'Payment Authorized Successfully: ' + payment, true);
          window.location.reload();
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.getVerifiedStatus = function () {
      switch ($scope.entity.verified) {
      case 1:
        return 'Verified';
      case 0:
        return 'Not Verified';
      case null:
        return 'Unknown';
      }
    };
    $scope.openCaptureModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'captureModalContent.html',
        controller: 'CaptureModalCtrl',
        resolve: {
          amount: function () {
            return $scope.entity.amount;
          }
        }
      });
      modalInstance.result.then(function (amount) {
        $scope.capture(amount);
      }, function () {
      });
    };
    $scope.verifyPayment = function () {
      var request = $http({
        method: 'get',
        url: '/admin/'  + $scope.mode + '/payment/' + $scope.entity.id + '/verify'
      });

      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data.payment);
          $scope.alerts.addAlert('success', 'Payment Verified successfully: ' + payment, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
          window.location.reload();
        }
        window.scrollTo(0, 0);
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
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
        url: '/admin/' + $scope.mode + '/' + $scope.entity.merchant_id + '/payments/' + $scope.entity.id + '/capture',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Payment Captured', true);
          $scope.entity.status = 'captured';
          $scope.entity.amount = captureAmount;
          window.location.reload();
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
      if (data.amount > unrefundedAmount) {
        $scope.alerts.addAlert('danger', 'Refund amount should be an integer and less than amount minus amount refunded.', true);
        return;
      }

      var request = $http({
        method: 'post',
        url: '/admin/' + $scope.mode + '/' + $scope.entity.merchant_id + '/payments/' + $scope.entity.id + '/refund',
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
          window.location.reload();
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
      var request = $http.get('/admin/' + $scope.mode + '/payments/' + $scope.entity.id + '/refunds');
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

    $scope.amount = (amount/100).toFixed(2);
    $scope.comment = '';

    $scope.valid = function(amount, comment) {
      return amount > 0 && comment.length > 5;
    }

    $scope.setAmount = function(isPartial) {
      if (isPartial) {
        this.refund_amount = '';
      } else {
        this.refund_amount = $scope.amount;
      }
    }

    $scope.ok = function (amount, comment) {
      // We get amount in INR
      var data = {
        amount: amount*100
      };
      if (comment !== '') {
        data.notes = {
          admin_comment: comment
        };
      }
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
