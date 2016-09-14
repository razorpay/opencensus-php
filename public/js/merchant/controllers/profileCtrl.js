app.controller('ProfileCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {

    var fetchBalance = function() {
      var request = $http.get('/' + $scope.mode + '/balance');
      request.success(function (result) {
        if(result.success) {
          $scope.balance = result.data.balance;
          $scope.credits = result.data.credits;
        }

        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      }).error(function () {
        $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      });
    }

    fetchBalance();
  }
])
