//Merchant List controller
app.controller('MerchantsCtrl', ['$scope', '$http',
  function($scope, $http) {

  $scope.merchants = {};

  $scope.count = 0;

  generateTable();

  function generateTable() {
    if($scope.pending){
      var request = $http.get("/admin/merchant/list?pending=true");
    }
    else{
      var request = $http.get("/admin/merchant/list");
    }

    request
    .success(function(data){
      if(data.success) {
        $scope.merchants = data.data.data;

        $scope.count = data.data.count;

        angular.forEach($scope.merchants, function(i){
          i.activation_progress = parseInt((i.merchant_details.steps_finished.length * 100)/ 5);    
        });     

      }
    });
  }
}]);