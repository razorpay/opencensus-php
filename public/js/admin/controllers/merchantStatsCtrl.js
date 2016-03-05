//Pricing List controller
app.controller('MerchantStatsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.data = [];
    $scope.resource = 'payment';
    $scope.mode = 'test';
    $scope.merchant_id = '';
    $scope.sort = 'total_amount';

    $scope.fetchAllAggregations = function(mode, resource, sort) {
      var request = $http.get('/admin/'+mode + '/merchants/aggregations/' + resource, {
        params: {
          sort: sort
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.data = data.data;
        }
      });
    }

    $scope.$watch('mode + sort + resource', function() {
      $scope.go('');
    })

    $scope.go = function(merchant_id) {
      if (merchant_id === "") {
        // We get all aggregations
        $scope.fetchAllAggregations($scope.mode, $scope.resource, $scope.sort);
      }
    }

    // Call go once
    $scope.go('');

  }
]);
