//Entities Listing Controller
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
    $scope.filter = 'all';
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
    $scope.regenerate = regenerate;
    function clear(field) {
      if (field === 'id')
        $scope.entity.id = '';
      if (field === 'skip')
        $scope.entity.skip = 0;
    }
    function regenerate(status) {
      $scope.filter = status;
      clear('skip');
      clear('id');
      generateTable();
    }
    function generateTable() {
      if (!$scope.entity.type) {
        console.log('Error: No Entity Type Sepcified');
        return;
      }
      var query = 'count=10' + '&skip=' + $scope.entity.skip;
      if ($scope.filter !== 'all') {
        query += '&status=' + $scope.filter;
      }
      if ($scope.entity.id === '')
        var request = $http.get('/' + $scope.mode + '/' + $scope.entity.type + 's?' + query);
      else
        var request = $http.get('/' + $scope.mode + '/' + $scope.entity.type + 's/' + $scope.entity.id);
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