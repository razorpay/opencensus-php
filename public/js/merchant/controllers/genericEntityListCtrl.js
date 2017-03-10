"use strict";
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
  function ($scope, $http, alertsFactory, $state, getStatusClass, $modal) {
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

    function generateTable() {
      if (!$scope.entity.type) {
        console.log('Error: No Entity Type Sepcified');
        return;
      }

      $scope.query.skip = $scope.entity.skip;

      var request;

      var params = {};
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

      params.query_params = q;
      params.mode = $scope.mode;

      if ($scope.entity.type === 'payment') {
        if ($scope.entity.id === '') {
          params.route_name = 'payment_fetch_multiple';
        } else {
          params.route_name = 'payment_fetch_by_id';
        }
      }

      if ($scope.entity.id === '') {
        request = $http.get('/generic', {
          params: params
        });
      }
      else {
        var url = location.origin + '/#/app/payments/' + $scope.entity.id;
        window.open(url, '_blank');

        return;
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
