//Transactions Listing Controller
app.controller('TransactionsListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
  function($scope, $http, modeFactory, alertsFactory, $state){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.transactions = {
        data: {},
        id: '',
        count: 0,
        countStart: 0,
        countEnd: 0,
        skip: 0
    };

    generateTable();
    
    $scope.next= function() {
      clear('id');
      $scope.transactions.skip += 10;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.transactions.skip -= 10;
      generateTable();
    }

    $scope.search= function() {
      clear('skip');
      generateTable();
    }

    $scope.regenerate = regenerate;

    $scope.getStatusClass = function(status) {
      var mapper = {
        open: "bg-light",
        authorized: "bg-info",
        captured: "bg-success",
        failed: "bg-danger"
      }
      return mapper[status];
    }

    function clear(field){
      if(field === 'id')
        $scope.transactions.id = '';
      if(field === 'skip')
        $scope.transactions.skip = 0;
    }

    function regenerate(){
      clear('skip');
      clear('id');
      generateTable();
    }

    function generateTable() {
      $scope.alerts.addAlert('info', "Processing... ", true);
      var query =
        "count=10" +
        "&skip="+ $scope.transactions.skip;

      if($scope.transactions.id === '')
        var request = $http.get("/" + modeFactory.getMode() +  "/transactions?" + query);
      else
        var request = $http.get("/" + modeFactory.getMode() +  "/transactions/" + $scope.transactions.id);
      
      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.transactions.data = data.data.data;

          $scope.transactions.count = data.data.count;

          $scope.transactions.countStart = $scope.transactions.skip + 1;

          if(data.data.count === 0) 
            $scope.transactions.countEnd = $scope.transactions.countStart;

          else 
            $scope.transactions.countEnd = $scope.transactions.countStart + $scope.transactions.count -1;

          $scope.allowPrev = $scope.transactions.countStart !== 1;
          
          $scope.allowNext = $scope.transactions.count >= 10;
        }
        else {
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });            
        }          
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    }
}]);