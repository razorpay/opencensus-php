// Merchant List controller
app.controller('MerchantsCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  function($scope, $http, $state, $stateParams) {
    $scope.merchant_type_request = $stateParams.type;
    $scope.merchants = {};
    $scope.count = 0;

    $scope.regenerate = function() {
      $scope.merchant_type_request = $scope.merchant_type_request
        ? $scope.merchant_type_request
        : 'activated';
      $scope.sub_accounts = {
        all: false,
        id: '',
      };
      $scope.search_query = '';
      $scope.filter();
    };

    $scope.filter = function() {
      var query = {};
      switch ($scope.merchant_type_request) {
        case 'activated':
          query.account_status = 'activated';
          break;

        case 'pending':
          query.account_status = 'pending';
          break;

        case 'dead':
          query.account_status = 'dead';
          break;

        case 'archived':
          query.account_status = 'archived';
          break;

        case 'suspended':
          query.account_status = 'suspended';
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

      generate(query);
    };

    $scope.regenerate();

    $scope.changeMerchantTypeUrl = function() {
      $state.go('app.merchants.list', { type: $scope.merchant_type_request });
    };

    function generate(query) {
      // Pending is a different view, and we only filter on that
      if ($scope.pending) {
        query = {
          account_status: 'pending',
          sub_accounts: query.sub_accounts,
        };
      }

      if ($scope.search_query !== '') {
        query.q = $scope.search_query;
      }

      var data = {
        route_name: 'admin_fetch_merchants_new',
        query_params: query,
      };

      var request = $http.get('/admin/generic', {
        params: data,
      });

      request.success(function(data) {
        if (data.success) {
          $scope.merchants = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }
  },
]);
