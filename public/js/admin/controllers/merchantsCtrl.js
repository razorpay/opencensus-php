//Merchant List controller
app.controller('MerchantsCtrl', ['$scope', '$http',
  function($scope, $http) {

  //Aggreagates
  $scope.merchants = {};

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
        $scope.merchants = data.data;

        angular.forEach($scope.merchants, function(i){
          i.activation_progress = parseInt((i.merchant_details.steps_finished.length * 100)/ 5);    
        });

        console.log($scope.merchants);
        

      }
    });
  }
}]);