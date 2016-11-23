app.controller('ProfileCtrl', [
  '$scope',
  '$http',

  function ($scope, $http) {

    var getCreditsData = function() {

      $scope.creditsData = '';
      var url = '/' + $scope.mode + '/credits';
      var request = $http.get(url);

      request.success(function (result) {
        if (result.success) {
          $scope.creditsData = result.data;
        }
      });
    };

    getCreditsData();

    var fetchBalance = function() {

      var request = $http.get('/' + $scope.mode + '/balance');
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
