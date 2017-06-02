//Pricing List controller
app.controller('MerchantStatsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  function($scope, $http, alertsFactory, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.data = [];
    $scope.mode = 'live';
    $scope.merchant_id = '';
    $scope.sort = 'total_amount';
    $scope.duration_count = 1;
    $scope.type = 'month';
    $scope.count = 10;
    $scope.stats = {
      count: 0,
      countStart: 0,
      countEnd: 0,
      skip: 0,
    };

    $scope.next = function() {
      $scope.stats.skip += $scope.count;
      $scope.go('');
    };

    $scope.prev = function() {
      $scope.stats.skip -= $scope.count;
      $scope.go('');
    };

    $scope.fetchAllAggregations = function(
      mode,
      sort,
      skip,
      count,
      duration_count,
      type
    ) {
      var request = $http.get('/admin/' + mode + '/merchants/aggregations', {
        params: {
          sort: sort,
          skip: skip,
          count: count,
          duration_count: duration_count,
          type: type,
        },
      });

      request.success(function(data) {
        if (data.success) {
          $scope.data = data.data;
          $scope.stats.count = data.data.length;
          $scope.stats.countStart = $scope.stats.skip + 1;
          if ($scope.stats.count === 0) {
            $scope.stats.countEnd = $scope.stats.countStart;
          } else {
            $scope.stats.countEnd =
              $scope.stats.countStart + $scope.stats.count - 1;
          }
          $scope.allowPrev = $scope.stats.countStart != 1;
          $scope.allowNext = $scope.stats.count >= $scope.count;
        }
      });
    };

    $scope.fetchAllAggregationsForMerchant = function(
      mid,
      mode,
      sort,
      duration_count,
      type
    ) {
      var request = $http.get(
        '/admin/' + mode + '/merchants/' + mid + '/aggregations',
        {
          params: {
            sort: sort,
            duration_count: duration_count,
            type: type,
          },
        }
      );

      request.success(function(data) {
        if (data.success) {
          $scope.data = data.data;
          $scope.stats.skip = 0;
          $scope.stats.countStart = 1;
          $scope.stats.countEnd = 1;
        }
      });
    };

    $scope.$watch('mode + type + sort', function() {
      $scope.go('');
    });

    $scope.go = function(merchant_id) {
      if (merchant_id === '') {
        // We get all aggregations
        $scope.fetchAllAggregations(
          $scope.mode,
          $scope.sort,
          $scope.stats.skip,
          $scope.count,
          $scope.duration_count,
          $scope.type
        );
      } else {
        $scope.fetchAllAggregationsForMerchant(
          merchant_id,
          $scope.mode,
          $scope.sort,
          $scope.duration_count,
          $scope.type
        );
      }
    };

    // Call go once
    $scope.go('');
  },
]);
