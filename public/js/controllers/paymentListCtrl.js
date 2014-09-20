//Payments Listing Controller
app.controller('PaymentsListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
  function($scope, $http, modeFactory, alertsFactory, $state){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.payments = {
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
      $scope.payments.skip += 10;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.payments.skip -= 10;
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
        $scope.payments.id = '';
      if(field === 'skip')
        $scope.payments.skip = 0;
    }

    function regenerate(){
      clear('skip');
      clear('id');
      generateTable();
    }

    function generateTable() {
      var query =
        "count=10" +
        "&skip="+ $scope.payments.skip;

      if($scope.payments.id === '')
        var request = $http.get("/" + modeFactory.getMode() +  "/payments?" + query);
      else
        var request = $http.get("/" + modeFactory.getMode() +  "/payments/" + $scope.payments.id);
      
      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.payments.data = data.data.data;

          $scope.payments.count = data.data.count;

          $scope.payments.countStart = $scope.payments.skip + 1;

          if(data.data.count === 0) 
            $scope.payments.countEnd = $scope.payments.countStart;

          else 
            $scope.payments.countEnd = $scope.payments.countStart + $scope.payments.count -1;

          $scope.allowPrev = $scope.payments.countStart !== 1;
          
          $scope.allowNext = $scope.payments.count >= 10;
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