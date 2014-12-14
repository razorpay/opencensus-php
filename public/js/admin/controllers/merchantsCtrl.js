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
        console.log(data);
        $scope.merchants = data.data;
      }
    });
  }
}]);