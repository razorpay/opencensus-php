//Settlements Listing Controller
app.controller('SettlementsListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
  function($scope, $http, modeFactory, alertsFactory, $state){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.settlements = {
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
      $scope.settlements.skip += 10;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.settlements.skip -= 10;
      generateTable();
    }

    $scope.search= function() {
      clear('skip');
      generateTable();
    }

    $scope.regenerate = regenerate;

    function clear(field){
      if(field === 'id')
        $scope.settlements.id = '';
      if(field === 'skip')
        $scope.settlements.skip = 0;
    }

    function regenerate(){
      clear('skip');
      clear('id');
      generateTable();
    }

    function generateTable() {
      var query =
        "count=10" +
        "&skip="+ $scope.settlements.skip;

      if($scope.settlements.id === '')
        var request = $http.get("/" + modeFactory.getMode() +  "/settlements?" + query);
      else
        var request = $http.get("/" + modeFactory.getMode() +  "/settlements/" + $scope.settlements.id);
      
      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.settlements.data = data.data.data;

          $scope.settlements.count = data.data.count;

          $scope.settlements.countStart = $scope.settlements.skip + 1;

          if(data.data.count === 0) 
            $scope.settlements.countEnd = $scope.settlements.countStart;

          else 
            $scope.settlements.countEnd = $scope.settlements.countStart + $scope.settlements.count -1;

          $scope.allowPrev = $scope.settlements.countStart !== 1;
          
          $scope.allowNext = $scope.settlements.count >= 10;
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