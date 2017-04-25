// Payment Listing Controller
// Child of TransactionListCtrl
app.controller('PaymentListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  'statusClass',
  'user',
  function($scope, $http, alertsFactory, $state, getStatusClass, user) {
    $scope.getStatusClass = getStatusClass;

    user.identity().then(function(data) {
      $scope.user = data;
    });

    // This stores the orderIds, since we can't maintain that
    // inside items itself
    $scope.orders = {};

    $scope.$watch('entity.items', function(payments) {
      for (var i = payments.length - 1; i >= 0; i--) {
        var orderId = getOrderId(payments[i]);
        if (orderId !== null) {
          $scope.orders[payments[i].id] = orderId;
        }
      }
    });

    var getOrderId = function(payment) {
      var notes = payment.notes;
      if (notes === [] || notes === {}) {
        return null;
      }

      var validOrderIds = ['order_id', 'orderId'];
      var orderIdSuffix = '_order_id';

      for (var i = validOrderIds.length - 1; i >= 0; i--) {
        var validOrderId = validOrderIds[i];
        if (typeof notes[validOrderId] !== 'undefined') {
          return notes[validOrderId];
        }
      }

      // Now we try for suffixes
      var suffixLength = orderIdSuffix.length;
      for (var key in notes) {
        var index = -1 * suffixLength;
        var suffix = key.substr(index);
        if (suffix === orderIdSuffix) {
          return notes[key];
        }
      }

      // We couldn't find anything in payments
      return null;
    };

    // If any payment has an Order Id
    // This will return true
    $scope.hasOrderId = function() {
      return Object.keys($scope.orders).length > 0;
    };
  },
]);
