app.controller('CreditsCtrl', [
  '$scope',
  '$http',

  function ($scope, $http) {

    var getCreditsData = function () {

      $scope.creditsData = '';
      var params = {
        route_name: 'credits_fetch_multiple',
        mode: $scope.mode
      };

      var request = $http.get('/user/generic', {
        params: params
      });

      request.success(function (result) {
        if (result.success) {
          $scope.creditsData = result.data;
        }
      });
    };

    getCreditsData();

    var fetchBalance = function () {

      var params = {
        route_name: 'balance_fetch',
        mode: $scope.mode
      };

      var request = $http.get('/user/generic', {
        params: params
      });

      request.success(function (result) {
        if(result.success) {
          $scope.balance = result.data.balance;

          // Amount and Fee Credits
          $scope.credits = result.data.credits;
          $scope.fee_credits = result.data.fee_credits;
        }

        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      }).error(function () {
        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      });
    }

    fetchBalance();
  }
])
