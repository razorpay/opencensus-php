//Merchant List controller
app.controller('MerchantsCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {
    $scope.merchants = {};
    $scope.count = 0;

    $scope.regenerate = function () {
      $scope.merchant_type = '1';
      $scope.sub_accounts = {
        'all': false,
        'id': ''
      };
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

        case '5':
          query.dead       = 1;
          break;

        case '6':
          query.archived   = 1;
          break;

        case '7':
          query.suspended   = 1;
          break;
      }

      // Adds a `sub_account` filter flag to the request
      // API will only return Marketplace sub-accounts
      if ($scope.sub_accounts.all === true) {
        query.sub_accounts = 1;
      }

      // Adds a `sub_account` id to the request - API
      // filters merchants against the parent_id field
      if ($scope.sub_accounts.id !== '') {
        query.sub_accounts = $scope.sub_accounts.id;
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
          pending: 1,
          sub_accounts: query.sub_accounts
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
