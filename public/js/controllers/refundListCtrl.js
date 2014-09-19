//Refunds Listing Controller
app.controller('RefundsListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
  function($scope, $http, modeFactory, alertsFactory, $state){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.refunds = {
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
      $scope.refunds.skip += 10;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.refunds.skip -= 10;
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
        $scope.refunds.id = '';
      if(field === 'skip')
        $scope.refunds.skip = 0;
    }

    function regenerate(){
      clear('skip');
      clear('id');
      generateTable();
    }

    function generateTable() {
      var query =
        "count=10" +
        "&skip="+ $scope.refunds.skip;

      if($scope.refunds.id === '')
        var request = $http.get("/" + modeFactory.getMode() +  "/refunds?" + query);
      else
        var request = $http.get("/" + modeFactory.getMode() +  "/refunds/" + $scope.refunds.id);
      
      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.refunds.data = data.data.data;

          $scope.refunds.count = data.data.count;

          $scope.refunds.countStart = $scope.refunds.skip + 1;

          if(data.data.count === 0) 
            $scope.refunds.countEnd = $scope.refunds.countStart;

          else 
            $scope.refunds.countEnd = $scope.refunds.countStart + $scope.refunds.count -1;

          $scope.allowPrev = $scope.refunds.countStart !== 1;
          
          $scope.allowNext = $scope.refunds.count >= 10;
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