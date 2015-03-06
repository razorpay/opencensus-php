//Merchant List controller
app.controller('MerchantsCtrl', ['$scope', '$http',
  function($scope, $http) {

  $scope.merchants = {};
  $scope.count = 0;
  generate();

  $scope.regenerate = function() {
    $scope.merchant_type = "";
    generate();
  }
  

  $scope.filter = function(){
    var query = ""
    switch($scope.merchant_type) {
      case '1':
          query="activated=1";
          break;
      case '2':
          query="activated=0";
          break;
      case '3':
          query="pending=1";
          break;
      case '4':
          query="confirmed=1";
          break;
      case '5':
          query="dead=1";
          break;
      default:
          query=null;
    }
    generate(query);
  }

  function generate(query) {
    if($scope.pending){
      var url = "/admin/merchant/list?pending=1";
    }
    else{
      var url = "/admin/merchant/list";
      if(query != null) {
        url = url + "?" + query;
      }
    }

    var request = $http.get(url);
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