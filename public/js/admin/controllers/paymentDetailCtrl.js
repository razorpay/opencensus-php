'use strict';
/**
 * Single Payment Details controller
 * Child of TransactionDetailCtrl
 */
app
  .controller('PaymentDetailCtrl', [
    '$scope',
    '$http',
    '$stateParams',
    '$modal',
    'alertsFactory',
    'transformRequestAsFormPost',
    'statusClass',
    'displayClass',
    'displayValue',
    '$state',
    function(
      $scope,
      $http,
      $stateParams,
      $modal,
      alertsFactory,
      transformRequestAsFormPost,
      getStatusClass,
      displayClass,
      displayValue,
      $state
    ) {
      $scope.displayClass = displayClass;
      $scope.getStatusClass = getStatusClass;
      $scope.displayValue = displayValue;

      // Keys currently added the to good-looking view
      var shownByDefault = [
        'analytics',
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
        'transfer_id',
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
          if (
            $scope.entity.hasOwnProperty(key) &&
            shownByDefault.indexOf(key) < 0
          ) {
            keys.push(key);
          }
        }
        return keys;
      };

      $scope.openRefundModal = function() {
        var modalInstance = $modal.open({
          templateUrl: 'refundModalContent.html',
          controller: 'RefundModalCtrl',
          resolve: {
            amount: function() {
              return $scope.entity.amount - $scope.entity.amount_refunded;
            },
            currency: function() {
              return $scope.entity.currency;
            },
          },
        });
        modalInstance.result.then(function(data) {
          $scope.refund(data);
        }, $.noop);
      };
      $scope.refundAuthorized = function() {
        var data = {
          route_name: 'payment_authorize_refund',
          merchant_id: $scope.entity.merchant_id,
          mode: $scope.mode,
          url_params: {
            '{id}': $scope.entity.id,
          },
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              var payment = JSON.stringify(data.data);
              $scope.alerts.addAlert(
                'success',
                'Payment Refunded Successfully: ' + payment,
                true
              );
              window.location.reload();
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
      $scope.authorizeFailedPayment = function() {
        var data = {
          route_name: 'payment_authorize_failed',
          url_params: {
            '{id}': $scope.entity.id,
          },
          mode: $scope.mode,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              var payment = JSON.stringify(data.data.payment);
              $scope.alerts.addAlert(
                'success',
                'Payment Authorized Successfully: ' + payment,
                true
              );
              window.location.reload();
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
      $scope.getVerifiedStatus = function() {
        switch ($scope.entity.verified) {
          case 1:
            return 'Verified';
          case 0:
            return 'Not Verified';
          case null:
            return 'Unknown';
          case 2:
            return 'Verify Error';
        }
      };
      $scope.openCaptureModal = function() {
        var modalInstance = $modal.open({
          templateUrl: 'captureModalContent.html',
          controller: 'CaptureModalCtrl',
          resolve: {
            amount: function() {
              return $scope.entity.amount;
            },
            currency: function() {
              return $scope.entity.currency;
            },
          },
        });
        modalInstance.result.then(
          function(amount) {
            $scope.capture(amount);
          },
          function() {}
        );
      };
      $scope.verifyPayment = function() {
        var data = {
          route_name: 'payment_verify',
          url_params: {
            '{id}': $scope.entity.id,
          },
          mode: $scope.mode,
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              var payment = JSON.stringify(data.data.payment);
              $scope.alerts.addAlert(
                'success',
                'Payment Verified successfully: ' + payment,
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
              window.location.reload();
            }
            window.scrollTo(0, 0);
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.capture = function(amount) {
        var captureAmount = parseInt(amount);
        if (!captureAmount) {
          $scope.alerts.addAlert('danger', 'Invalid capture amount', true);
          return;
        }
        var data = {
          amount: captureAmount,
          currency: $scope.entity.currency,
        };
        var paymentCaptureData = {
          route_name: 'payment_capture',
          merchant_id: $scope.entity.merchant_id,
          mode: $scope.mode,
          url_params: {
            '{id}': $scope.entity.id,
          },
          body: data,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: paymentCaptureData,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert('success', 'Payment Captured', true);
              $scope.entity.status = 'captured';
              $scope.entity.amount = captureAmount;
              window.location.reload();
            } else {
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      // Create dispute
      $scope.createDispute = function(data) {
        var params = {
          route_name: 'payment_disputes',
          url_params: {
            '{id}': $scope.entity.id,
          },
          mode: $scope.mode,
          body: data,
        };

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: params,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (data.data.workflow_id) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                window.location.reload();
              }
            } else {
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.refund = function(data) {
        data.amount = parseInt(data.amount);
        var unrefundedAmount =
          parseInt($scope.entity.amount) -
          parseInt($scope.entity.amount_refunded);
        if (data.amount > unrefundedAmount) {
          $scope.alerts.addAlert(
            'danger',
            'Refund amount should be an integer and less than amount minus amount refunded.',
            true
          );
          return;
        }
        var paymentRefundData = {
          route_name: 'payment_refund',
          merchant_id: $scope.entity.merchant_id,
          mode: $scope.mode,
          url_params: {
            '{id}': $scope.entity.id,
          },
          body: data,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: paymentRefundData,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert('success', 'Payment Refunded', true);
              if (data.amount == unrefundedAmount)
                $scope.entity.refund_status = 'full';
              else $scope.entity.refund_status = 'partial';
              $scope.entity.amount_refunded =
                parseInt($scope.entity.amount_refunded) + data.amount;
              window.location.reload();
            } else {
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.openDisputeModal = function() {
        var modalInstance = $modal.open({
          templateUrl: 'disputeModalContent.html',
          controller: 'DisputeModalCtrl',
          resolve: {
            current: function() {
              return $scope.entity;
            },
            mode: function() {
              return $scope.mode;
            },
          },
        });
        modalInstance.result.then(function(data) {
          $scope.createDispute(data);
        }, $.noop);
      };

      $scope.showRefunds = function() {
        if ($scope.isRefundsCollapsed === false) {
          $scope.isRefundsCollapsed = true;
          return;
        }
        var data = {
          route_name: 'payment_fetch_refunds',
          merchant_id: $scope.entity.merchant_id,
          url_params: {
            '{id}': $scope.entity.id,
          },
          mode: $scope.mode,
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            $scope.alerts.resetAlerts();
            if (data.success) {
              $scope.entity.refunds = data.data.items;
              $scope.isRefundsCollapsed = false;
            } else {
              angular.forEach(data.errors, function(error) {
                $scope.alerts.addAlert('danger', error);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.showAnalytics = function() {
        if ($scope.isAnalyticsCollapsed === false) {
          $scope.isAnalyticsCollapsed = true;
          return;
        }
        var entityData = $scope.entity.id.split('_');
        var paymentId = entityData[entityData.length - 1];
        var data = {
          route_name: 'admin_fetch_entity_multiple',
          url_params: {
            '{type}': 'payment_analytics',
          },
          mode: $scope.mode,
          query_params: {
            payment_id: paymentId,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            $scope.alerts.resetAlerts();
            if (data.success) {
              $scope.entity.analytics = data.data.items[0];
              $scope.isAnalyticsCollapsed = false;
            } else {
              angular.forEach(data.errors, function(error) {
                $scope.alerts.addAlert('danger', error);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
    },
  ]) //Capture Modal Box Controller
  .controller('CaptureModalCtrl', [
    '$scope',
    '$modalInstance',
    'amount',
    'currency',
    function($scope, $modalInstance, amount, currency) {
      $scope.amount = amount;
      $scope.currency = currency;

      $scope.ok = function(amount) {
        $modalInstance.close(amount);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]) //Refund Modal Box Controller
  .controller('RefundModalCtrl', [
    '$scope',
    '$modalInstance',
    'amount',
    'currency',
    '$filter',
    function($scope, $modalInstance, amount, currency, $filter) {
      // This is displayed with 2 decimal places
      $scope.amount = $filter('propercurrency')(amount / 100, '');
      $scope.comment = '';
      $scope.currency = currency;

      $scope.valid = function(amount, comment) {
        amount = amount.replace(/[^0-9\.]+/g, '');
        return (
          amount > 0 &&
          (typeof comment !== 'undefined' ? comment.length > 5 : false)
        );
      };

      $scope.setAmount = function(isPartial) {
        if (isPartial) {
          this.refund_amount = '';
        } else {
          this.refund_amount = $scope.amount;
        }
      };

      $scope.ok = function(amount, comment) {
        // We get amount in INR
        amount = parseFloat(amount.replace(/[, ]/, ''));

        var data = {
          amount: amount * 100,
        };
        if (comment !== '') {
          data.notes = {
            admin_comment: comment,
          };
        }
        $modalInstance.close(data);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
