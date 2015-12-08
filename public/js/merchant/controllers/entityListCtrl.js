/**
 * Entities Listing Controller
 */
app.controller('EntityListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  function ($scope, $http, alertsFactory, $state) {
    //Intialise alerts and scope functions
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
      email: ''
    };

    $scope.generate = function (entity) {
      $scope.entity.type = entity;
      generateTable();
    };

    $scope.next = function () {
      clear('id');
      $scope.entity.skip += 10;
      generateTable();
    };

    $scope.prev = function () {
      clear('id');
      $scope.entity.skip -= 10;
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
      if (field === 'id')
        $scope.entity.id = '';
      if (field === 'skip')
        $scope.entity.skip = 0;
    }

    function generateTable() {
      if (!$scope.entity.type) {
        console.log('Error: No Entity Type Sepcified');
        return;
      }

      $scope.query.skip = $scope.entity.skip;

      // /live/payments
      var baseuRL = '/' + $scope.mode + '/' + $scope.entity.type + 's';
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

      // Figure out the proper URL to hit if we are fetching just a single
      // entity or a collection
      if ($scope.entity.id === '') {
        request = $http.get(baseuRL, {params: q});
      }
      else {
        request = $http.get(baseuRL + '/' + $scope.entity.id, {
          params: q
        });
      }

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity.items = data.data.items;
          $scope.entity.count = data.data.count;
          $scope.entity.countStart = $scope.entity.skip + 1;
          if (data.data.count == 0)
            $scope.entity.countEnd = $scope.entity.countStart;
          else
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count - 1;
          $scope.allowPrev = $scope.entity.countStart != 1;
          $scope.allowNext = $scope.entity.count >= 10;
        } else {
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);
