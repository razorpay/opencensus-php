//Pricing List controller
app.controller('MerchantStatsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  function($scope, $http, alertsFactory, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.data = [];
    $scope.resource = 'payment';
    $scope.mode = 'live';
    $scope.merchant_id = '';
    $scope.sort = 'total_amount';
    $scope.duration_count = '';
    $scope.period = '';
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
      resource,
      sort,
      skip,
      count,
      days
    ) {
      var request = $http.get(
        '/admin/' + mode + '/merchants/aggregations/' + resource,
        {
          params: {
            sort: sort,
            skip: skip,
            count: count,
            days: days,
          },
        }
      );

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

    $scope.fetchAllAggregationsForMerchant = function(mid, mode, resource) {
      var request = $http.get(
        '/admin/' + mode + '/merchants/' + mid + '/aggregations/' + resource
      );

      request.success(function(data) {
        if (data.success) {
          $scope.data = [data.data];
          $scope.stats.skip = 0;
          $scope.stats.countStart = 1;
          $scope.stats.countEnd = 1;
        }
      });
    };

    $scope.$watch('mode + sort + resource', function() {
      $scope.go('');
    });

    $scope.go = function(merchant_id) {
      if (merchant_id === '') {
        var days = 0;
        if ($scope.duration_count !== '' && $scope.period !== '') {
          days = $scope.duration_count * $scope.period;
        }

        // We get all aggregations
        $scope.fetchAllAggregations(
          $scope.mode,
          $scope.resource,
          $scope.sort,
          $scope.stats.skip,
          $scope.count,
          days
        );
      } else {
        $scope.fetchAllAggregationsForMerchant(
          merchant_id,
          $scope.mode,
          $scope.resource
        );
      }
    };

    // Call go once
    $scope.go('');
  },
]);
