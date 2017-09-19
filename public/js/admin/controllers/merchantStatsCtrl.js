//Pricing List controller
app.controller('MerchantStatsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$stateParams',
  'dateFactory',
  function(
    $scope,
    $http,
    alertsFactory,
    transformRequestAsFormPost,
    $stateParams,
    dateFactory
  ) {
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

    $scope.from_timestamp =
      moment()
        .subtract(7, 'days')
        .unix() * 1000;
    $scope.to_timestamp = moment().unix() * 1000;

    $scope.fetchMerchantAnalyticsStats = function() {
      $scope.date = dateFactory.getHandler($scope);
      $scope.date.dateOptions['showWeeks'] = false;
      $scope.date.dateOptions['maxDate'] = moment(); // Avoid selection of date after today
      var merchantId = $stateParams.id;
      var data = {
        route_name: 'merchant_analytics',
        body: {
          filters: {
            default: [
              {
                merchant_id: [merchantId],
                created_at: {
                  gte: $scope.from_timestamp / 1000,
                  lte: $scope.to_timestamp / 1000,
                },
              },
            ],
            filter_success_trans: [
              {
                merchant_id: [merchantId],
                created_at: {
                  gte: $scope.from_timestamp / 1000,
                  lte: $scope.to_timestamp / 1000,
                },
                status: ['captured', 'authorized'],
              },
            ],
          },
          aggregations: {
            total_payments: {
              agg_type: 'count',
              details: {
                index: 'payment',
                column: 'base_amount',
              },
            },
            total_settlements: {
              agg_type: 'count',
              details: {
                index: 'settlement',
                column: 'base_amount',
              },
            },
            total_refunds: {
              agg_type: 'count',
              details: {
                index: 'refund',
                column: 'base_amount',
              },
            },
            payments_volume: {
              agg_type: 'sum',
              details: {
                index: 'payment',
                column: 'base_amount',
              },
            },
            recent_balance: {
              agg_type: 'recent',
              details: {
                index: 'balance',
                column: 'base_amount',
              },
            },
            recent_payments: {
              agg_type: 'recent',
              details: {
                index: 'payment',
                column: 'base_amount',
                result_fields: ['id', 'status', 'created_at'],
              },
            },
            recent_refunds: {
              agg_type: 'recent',
              details: {
                index: 'refund',
                column: 'base_amount',
                result_fields: ['id', 'status', 'created_at'],
              },
            },
            recent_settlements: {
              agg_type: 'recent',
              details: {
                index: 'settlement',
                column: 'base_amount',
                result_fields: ['id', 'status', 'created_at'],
              },
            },
            recent_transactions: {
              agg_type: 'recent',
              details: {
                index: 'transaction',
                result_fields: ['created_at'],
              },
            },
            payment_method_bars: {
              agg_type: 'percent',
              details: {
                index: 'payments',
                column: 'base_amount',
                group_by: ['method'],
              },
            },
            transaction_histogram: {
              agg_type: 'sum',
              details: {
                index: 'transaction',
                column: 'base_amount',
                group_by: ['histogram_daily'],
              },
            },
            successful_transaction: {
              agg_type: 'count',
              filter_key: 'filter_success_trans',
              details: {
                index: 'transaction',
                column: 'base_amount',
                group_by: ['histogram_weekly'],
              },
            },
          },
        },
        merchant_id: merchantId,
      };

      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: data,
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.merchant_analytics = data.data;
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };
  },
]);
