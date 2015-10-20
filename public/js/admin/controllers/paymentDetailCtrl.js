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
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost) {
    $scope.getStatusClass = function (status) {
      var mapper = {
        created: 'bg-light',
        authorized: 'bg-info',
        captured: 'bg-success',
        refunded: 'bg-primary',
        failed: 'bg-danger'
      };
      return mapper[status];
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
      modalInstance.result.then(function (amount) {
        $scope.refund(amount);
      }, function () {
      });
    };

    $scope.refundAuthorized = function() {
      var request = $http({
        method: 'post',
        url: '/admin/' + $scope.mode + '/' + $scope.entity.merchant_id + '/payments/' + $scope.entity.id + '/refund_authorized'
      });

      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data);
          $scope.alerts.addAlert('success', 'Payment Refunded Successfully: ' + payment, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
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
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
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
        url: '/admin/payment/' + $scope.entity.id + '/verify'
      });
      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data.payment);
          $scope.alerts.addAlert('success', 'Payment Verified successfully: ' + payment, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
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
        } else {
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.refund = function (amount) {
      var refundAmount = parseInt(amount);
      var unrefundedAmount = parseInt($scope.entity.amount) - parseInt($scope.entity.amount_refunded);
      if (!refundAmount || refundAmount > unrefundedAmount) {
        $scope.alerts.addAlert('danger', 'Refund amount should be an integer and less than amount minus amount refunded.', true);
        return;
      }
      var data = { amount: refundAmount };
      var request = $http({
        method: 'post',
        url: '/admin/' + $scope.mode + '/' + $scope.entity.merchant_id + '/payments/' + $scope.entity.id + '/refund',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Payment Refunded', true);
          if (refundAmount == unrefundedAmount)
            $scope.entity.refund_status = 'full';
          else
            $scope.entity.refund_status = 'partial';
          $scope.entity.amount_refunded = parseInt($scope.entity.amount_refunded) + refundAmount;
        } else {
          angular.forEach(data.errors, function (value, key) {
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
          angular.forEach(data.errors, function (error, key) {
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
    $scope.amount = amount;
    $scope.ok = function (amount) {
      $modalInstance.close(amount);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
