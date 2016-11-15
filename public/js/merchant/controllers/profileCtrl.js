app.controller('ProfileCtrl', [
  '$scope',
  '$http',

  function ($scope, $http) {

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

    function getCreditsLog() {

      var request = $http.get('/' + $scope.mode + '/credits');

      // request.success(function (result) {

      //   if (result.success) {
      //     $scope.creditsLog = result.data;
      //   }

      //   $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      // }).error(function() {
      //   $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      // });
    }
  }
])