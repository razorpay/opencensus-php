/**
 * Generic Entities Listing Controller [Currently handling Payments]
 */
app.controller('GenericEntityListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  'statusClass',
  '$modal',
  'user',
  function ($scope, $http, alertsFactory, $state, getStatusClass, $modal, user) {
    // Intialise alerts and scope functions
    $scope.getStatusClass = getStatusClass;
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity = {
      items: {},
      id: '',
      count: 0,
      countStart: 0,
      countEnd: 0,
      skip: 0
    };

    $scope.query = {
      count: 10,
      status: 'all',
      contact: '',
      email: '',
      amount: '',
      notes: '',
      receipt: '',
      payment_id: ''
    };

    $scope.generate = function (entity) {
      $scope.entity.type = entity;
      generateTable();
    };

    $scope.next = function () {
      clear('id');
      $scope.entity.skip += $scope.query.count;
      generateTable();
    };

    $scope.prev = function () {
      clear('id');
      // Prevents double click issues so we don't go negative
      $scope.entity.skip = Math.max($scope.entity.skip - $scope.query.count, 0);
      generateTable();
    };

    $scope.search = function () {
      clear('skip');
      generateTable();
    };

    $scope.regenerate = function regenerate() {
      clear('skip');
      clear('id');
      generateTable();
    };

    function clear(field) {
      if (field === 'id') {
        $scope.entity.id = '';
      }
      if (field === 'skip') {
        $scope.entity.skip = 0;
      }
    }

    user.identity().then(function (data) {
      $scope.user = data;
    });

    // This stores the orderIds, since we can't maintain that inside items itself
    $scope.orders = {};

    $scope.$watch('entity.items', function (payments) {
      for (var i = payments.length - 1; i >= 0; i--) {
        var orderId = getOrderId(payments[i]);
        if (orderId !== null) {
          $scope.orders[payments[i].id] = orderId;
        };
      };
    });

    var getOrderId = function (payment) {
      var notes = payment.notes;
      if (notes === [] || notes === {}) {
        return null;
      };

      var validOrderIds = ['order_id', 'orderId'];
      var orderIdSuffix = "_order_id";

      for (var i = validOrderIds.length - 1; i >= 0; i--) {
        var validOrderId = validOrderIds[i];
        if (typeof notes[validOrderId] !== 'undefined') {
          return notes[validOrderId];
        };
      };

      // Now we try for suffixes
      var suffixLength = orderIdSuffix.length;
      for (var key in notes) {
        var index = -1 * suffixLength;
        var suffix = key.substr(index);
        if (suffix === orderIdSuffix) {
          return notes[key];
        };
      };

      // We couldn't find anything in payments
      return null;
    };

    // If any payment has an Order Id, this will return true
    $scope.hasOrderId = function () {
      return Object.keys($scope.orders).length > 0;
    };

    $scope.getStatusClass = function (status) {
      var mapper = {
        created: 'bg-light',
        attempted: 'bg-info',
        paid: 'bg-success'
      };
      return mapper[status];
    };

    function generateTable() {
      if (!$scope.entity.type) {
        console.log('Error: No Entity Type Sepcified');
        return;
      }

      $scope.query.skip = $scope.entity.skip;

      // /live/payments
      var baseURL = '/' + $scope.mode + '/' + $scope.entity.type + 's';

      // TODO: This is a hack, will need a proper pluralizer
      if ($scope.entity.type === 'batch') {
        baseURL = '/' + $scope.mode + '/' + $scope.entity.type + 'es';
      }

      var request;

      var q = jQuery.extend({}, $scope.query);

      if (q.status === 'all') {
        delete q.status;
      }

      if (q.email === '') {
        delete q.email;
      }

      if (q.contact === '') {
        delete q.contact;
      }

      if (q.amount !== '') {
        q.amount = q.amount*100;
      }
      else {
        delete q.amount;
      }

      if (q.notes === '') {
        delete q.notes;
      }
      q.type = 'merchant';
      q.merchant_id = '7O1Zj6BYJk3saU'; // To be made dynamic

      if ($scope.entity.type === 'payment') {
        if ($scope.entity.id === '') {
          q.route_name = 'payment_fetch_multiple';
        } else {
          q.route_name = 'payment_fetch_by_id';
        }
      } else if ($scope.entity.type === 'order') {
        if ($scope.entity.id === '') {
          q.route_name = 'order_fetch';
        } else {
          q.route_name = 'order_fetch_by_id';
        }
      } else if ($scope.entity.type === 'refund') {
        if ($scope.entity.id === '') {
          q.route_name = 'refund_fetch_multiple';
        } else {
          q.route_name = 'refund_fetch_by_id';
        }
      }

      // Figure out the proper URL to hit if we are fetching just a single
      // entity or a collection
      if ($scope.entity.id === '') {
        request = $http.get('/generic', {
          params: q
        });
      }
      else {
        q.id = $scope.entity.id;
        request = $http.get('/generic', {
          params: q
        });
      }

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity.items = data.data.items;
          $scope.entity.count = data.data.count;

          $scope.entity.countStart = $scope.entity.skip + 1;

          if (data.data.count === 0) {
            $scope.entity.countEnd = $scope.entity.countStart;
          }
          else {
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count - 1;
          }

          $scope.allowPrev = $scope.entity.countStart != 1;
          $scope.allowNext = $scope.entity.count >= $scope.query.count;
        } else {
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);
