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

    $scope.fetchAllAggregations = function() {
      var request = $http.get('/admin/merchants/aggregations');
      request.success(function (data) {
        if (data.success) {
          $scope.data = data.data;
        }
      });
    }

    $scope.go = function(merchant_id) {
      if (mechant_id === "") {
        // We get all aggregations
        $scope.fetchAllAggregations();
      }
    }

  }
]);
