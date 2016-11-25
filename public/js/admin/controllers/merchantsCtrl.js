//Merchant List controller
app.controller('MerchantsCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {
    $scope.merchants = {};
    $scope.count = 0;

    $scope.regenerate = function () {
      $scope.merchant_type = '0';
      $scope.tags = '';
      $scope.filter();
    };

    $scope.filter = function () {
      var query = {};
      switch ($scope.merchant_type) {

        case '1':
          query.activated  = 1;
          break;

        case '2':
          query.activated  = 0;
          break;

        case '3':
          query.pending    = 1;
          break;

        case '4':
          query.confirmed  = 1;
          break;

        case '5':
          query.dead       = 1;
          break;

        case '6':
          query.archived   = 1;
          break;
      }

      // If we have tags in the list, send them as well
      if ($scope.tags !== '') {
        query.tags = $scope.tags;
      }

      generate(query);
    };

    $scope.regenerate();

    function generate(query) {
      var url = '/admin/merchant/list';

      // Pending is a different view, and we only filter on that
      if ($scope.pending) {
        query = {
          pending: 1
        };
      }

      var request = $http.get(url, {
        params: query
      });

      request.success(function (data) {
        if (data.success) {
          $scope.merchants = data.data.data;
          $scope.count = data.data.count;
          angular.forEach($scope.merchants, function (i) {
            i.activation_progress = parseInt($.parseJSON(i.steps_finished).length * 100 / 5);
            i.tags = i.tagged.map(function(tagModel) {
              return tagModel.tag_name;
            });
            delete i.tagged;
          });
        }
      });
    }
  }
]);
