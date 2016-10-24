//Dashboard Controllers
//Aggregations controller
app.controller('DashboardAggregationsCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {
    //Aggreagates
    $scope.aggregations = {};
    $scope.aggregations.data = {
      updated_at: 0,
      refunds: 0,
      payments: 0,
      settlements: 0,
      amount: 0
    };
    $scope.balance = 0;

    var request = $http.get('/' + $scope.mode + '/analytics/aggregations');
    request.success(function (result) {
      if (result.data) {
        if (result.data.payment && parseInt(result.data.payment.txn_count) !== 0)
          $scope.aggregations.data.updated_at = result.data.payment.updated_at;
        $scope.aggregations.data.amount = result.data.payment ? result.data.payment.total_amount / 100 : 0;
        $scope.aggregations.data.payments = result.data.payment ? result.data.payment.txn_count : 0;
        $scope.aggregations.data.refunds = result.data.refund ? result.data.refund.txn_count : 0;
        $scope.aggregations.data.settlements = result.data.settlement ? result.data.settlement.txn_count : 0;
      }
    });

    var fetchBalance = function() {
      var request = $http.get('/' + $scope.mode + '/balance');
      request.success(function (result) {
        if(result.success) {
          $scope.balance = result.data.balance;
          if (result.data.credits) {
            $scope.credits = result.data.credits;
            $scope.credits_type = 'amount';
          }
          if (result.data.fee_credits) {
            $scope.credits = result.data.fee_credits;
            $scope.credits_type = 'fee';
          }
        }

        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      }).error(function () {
        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      });
    }

    fetchBalance();

  }
])  //Payment methods aggregations
.controller('DashboardPaymentAggregationsCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {
    //Aggreagates
    $scope.paymentaggregations = {
      VISA: 0,
      MC: 0,
      MAES: 0,
      NETBANKING: 0,
      OTHER: 0
    };

    function parsePaymentAggregations(data) {
      var headers = [
        'CARD',
        'EMI',
        'NETBANKING',
        'WALLET'
      ], total = 0, result = {};

      // Convert to integers
      for (var i in headers) {
        var method = headers[i];
        data[method] = parseInt(data[method]);
      }

      // Calculate sums
      for (var i in headers) {
        var method = headers[i];
        total += data[method];
      }

      // Calculate percentages
      for (var i in headers) {
        var method = headers[i];
        result[method] = ((data[method] * 100)/total).toFixed(1);
      }

      // Return response
      return result;
    }
    var request = $http.get('/' + $scope.mode + '/analytics/payment/aggregations');
    request.success(function (result) {
      if (result.data) {
        $scope.paymentaggregations = parsePaymentAggregations(result.data);
      }
    });
  }
])  //Dashboard graphs/date picker controller
.controller('DashboardGraphsCtrl', [
  '$scope',
  '$http',
  'dateFactory',
  'user',
  '$state',
  function ($scope, $http, dateFactory, $user, $state) {
    $user.identity(true).then(function(user) {
      if (!user.current) {
        $state.go('app.profile');
      }
    })
    initialiseStatType();
    initialiseGraphs();
    //Watches changes in parameters and trigger regeneration fo graph if any changes
    $scope.$watch('showSpline', function () {
      $scope.refreshGraph = !$scope.refreshGraph;
    });
    //@todo once angular 1.3 is stable switch to watchgroup
    $scope.$watch('statType + date.startDate + date.endDate', function () {
      generateGraphs();
    });
    function initialiseStatType() {
      //Stat Type handlers
      $scope.statType = 'day';
      $scope.stats = {
        day: 'Daily',
        week: 'Weekly',
        month: 'Monthly',
        year: 'Yearly'
      };
      $scope.setStat = function (type) {
        $scope.statType = type;
      };
    }
    function initialiseGraphs() {
      //Date Handlers
      $scope.date = dateFactory.getHandler($scope);
      //Graphs Initalisers
      $scope.showSpline = true;
      $scope.refreshGraph = false;
      //Variable that is toggled whenever we want the graph to be refreshed
      var graphOptions = {
        data: [[
          0,
          0
        ]],
        options: {
          colors: [
            $scope.app.color.info
          ],
          series: { shadowSize: 3 },
          xaxis: {
            mode: 'time',
            timezone: 'browser'
          },
          yaxis: { font: { color: '#a1a7ac' } },
          grid: {
            hoverable: true,
            clickable: true,
            borderWidth: 0,
            color: '#dce5ec'
          },
          tooltip: true,
          tooltipOpts: {
            content: 'Date: %x <br/> Count: %y',
            defaultTheme: false,
            shifts: {
              x: 10,
              y: -25
            }
          }
        }
      };
      $scope.successfull = graphOptions;
      $scope.transactions = $.extend(true, {}, graphOptions);
      $scope.transactions.options.tooltipOpts.content = 'Date: %x <br/> Amount: \u20B9%y';
    }
    function generateGraphs() {
      if (!$scope.date.startDate || !$scope.date.endDate)
        return;
      var from = parseInt($scope.date.startDate.getTime() / 1000) - 1;
      var to = parseInt($scope.date.endDate.getTime() / 1000) + 1;
      var request = $http.get('/' + $scope.mode + '/analytics/transactions?type=' + $scope.statType + '&from=' + from + '&to=' + to);
      request.success(function (data) {
        if (data.success) {
          $scope.successfull.data = [];
          $scope.transactions.data = [];
          angular.forEach(data.data, function (value, key) {
            $scope.successfull.data.push([
              parseInt(value.created_at) * 1000,
              parseInt(value.count)
            ]);
            $scope.transactions.data.push([
              parseInt(value.created_at) * 1000,
              parseInt(value.amount) / 100
            ]);
          });
          $scope.successfull.options.xaxis.minTickSize = getMinTickSize($scope.statType);
          $scope.transactions.options.xaxis.minTickSize = getMinTickSize($scope.statType);
          $scope.refreshGraph = !$scope.refreshGraph;
        }
      });
    }
    function getMinTickSize(statType) {
      if (statType === 'week') {
        return [
          7,
          'day'
        ];
      } else {
        return [
          1,
          statType
        ];
      }
    }
  }
]);
