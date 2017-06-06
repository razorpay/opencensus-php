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
    $scope.note = '';
    $scope.stats = {
      count: 0,
      countStart: 0,
      countEnd: 0,
      page: 1,
    };

    $scope.next = function() {
      $scope.stats.page += 1;
      $scope.go('');
    };

    $scope.prev = function() {
      $scope.stats.page -= 1;
      $scope.go('');
    };

    $scope.fetchAllAggregations = function(
      mode,
      sort,
      page,
      count,
      duration_count,
      type
    ) {
      var request = $http.get('/admin/' + mode + '/merchants/aggregations', {
        params: {
          sort: sort,
          page: page,
          count: count,
          duration_count: duration_count,
          type: type,
        },
      });

      request.success(function(data) {
        if (data.success) {
          $scope.data = data.data.data;
          $scope.stats.count = data.data.data.length;
          $scope.stats.countStart = data.data.from;
          $scope.stats.countEnd = data.data.to;
          $scope.allowPrev = data.data.prev_page_url !== null;
          $scope.allowNext = data.data.next_page_url !== null;
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
          $scope.stats.page = 1;
          $scope.stats.countStart = 1;
          $scope.stats.countEnd = 1;
        }
      });
    };

    $scope.$watch('mode + type + sort', function() {
      $scope.go($scope.merchant_id);
    });

    $scope.go = function(merchant_id) {
      var startOf = $scope.type === 'week' ? 'iso' + $scope.type : $scope.type;
      var startDate = moment()
        .subtract($scope.duration_count, $scope.type)
        .startOf(startOf)
        .format('MMMM Do, YYYY');
      $scope.note = 'Fetching results from ' + startDate + ' till today.';
      if (merchant_id === '') {
        // We get all aggregations
        $scope.fetchAllAggregations(
          $scope.mode,
          $scope.sort,
          $scope.stats.page,
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
  },
]);
