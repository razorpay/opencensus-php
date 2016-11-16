app.controller('ProfileCtrl', [
  '$scope',
  '$http',

  function ($scope, $http) {

    var getCreditsData = function() {

      $scope.creditsData = '';

      var url = '/' + $scope.mode + '/credits';
      
      $http.get(url).then(function successCallBack(dataResponse) {

        $scope.creditsData = dataResponse.data;

      },
      function errorCallBack(response) {
//        console.log('Error occured status', (response.status).toString());
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