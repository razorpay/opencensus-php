//Dashboard Controllers
//Aggregations controller
app.controller('DashboardAggregationsCtrl', ['$scope', '$http', 'modeFactory',
  function($scope, $http, modeFactory) {

  //Aggreagates
  $scope.aggregations = {};

  $scope.aggregations.data = {
                      success: 0,
                      amount: 0,
                      txns: 0
                  };

  var request = $http.get("/"+modeFactory.getMode()+"/analytics/aggregations");

  request.success(function(result){
    
    if (result.data !== null) {
        if (parseInt(result.data.txn_count) !== 0)
            $scope.aggregations.data.success = parseInt(result.data.successful_txn_count * 100/result.data.txn_count);
        $scope.aggregations.data.amount = result.data.total_amount;
        $scope.aggregations.data.txns = result.data.txn_count;
    }
  });
}])

//Dashboard graphs/date picker controller
.controller('DashboardGraphsCtrl', ['$scope', '$http', 'modeFactory', 'dateFactory',
  function($scope, $http, modeFactory, dateFactory) {

    
    initialiseStatType();
    initialiseGraphs();
    
    //Watches changes in parameters and trigger regeneration fo graph if any changes
    $scope.$watch('showSpline', function() {
       $scope.refreshGraph = !$scope.refreshGraph;
    });
  
    //@todo once angular 1.3 is stable switch to watchgroup
    $scope.$watch('statType + date.startDate + date.endDate',function() {
       generateGraphs();
    });

    function initialiseStatType(){
      //Stat Type handlers
      $scope.statType = 'day';

      $scope.stats = {day: "Daily", week: "Weekly", month: "Monthly", year: "Yearly"};

      $scope.setStat = function(type) {
        $scope.statType = type;
      };
    };

    function initialiseGraphs() {
      //Date Handlers
      $scope.date = dateFactory.getHandler($scope);
      
      //Graphs Initalisers
      $scope.showSpline = true;

      $scope.refreshGraph = false; //Variable that is toggled whenever we want the graph to be refreshed

      var graphData = {
        data: [ [0,0] ],
        options: {
          colors: [$scope.app.color.info, $scope.app.color.primary],
          series: { shadowSize: 3 },
          xaxis: {mode: 'time', timezone: "browser"},
          yaxis:{ font: { color: '#a1a7ac' }},
          grid: { hoverable: true, clickable: true, borderWidth: 0, color: '#dce5ec' },
          tooltip: true,
          tooltipOpts: {
            defaultTheme: false, 
            shifts: { x: 10, y: -25 }
          }
        }
      };

      $scope.successfull = graphData;
      $scope.transactions = graphData;
      
      $scope.successfull.options.tooltipOpts.content = 'Date: %x <br/> Count: %y';
      $scope.successfull.options.tooltipOpts.content = 'Date: %x <br/> Amount: %y';
    };

    function generateGraphs() {
      if(!$scope.date.startDate || !$scope.date.endDate) return;

      var from = parseInt(($scope.date.startDate.getTime())/1000) - 1;
      var to = parseInt(($scope.date.endDate.getTime())/1000) + 1;

      var request = $http.get("/" + modeFactory.getMode() + "/analytics/transactions?type="+$scope.statType+"&from=" + from + "&to="+ to);


      request.success(function(data){
        if(data.success){
          $scope.successfull.data = [];
          $scope.transactions.data = [];

          angular.forEach(data.data , function(value, key){
            $scope.successfull.data.push([
              parseInt(value.created_at)*1000,
              parseInt(value.count)
            ]);

            $scope.transactions.data.push([
              parseInt(value.created_at)*1000,
              parseInt(value.amount)
            ]);
          });

          $scope.successfull.options.xaxis.minTickSize = getMinTickSize($scope.statType);
          $scope.transactions.options.xaxis.minTickSize = getMinTickSize($scope.statType);

          $scope.refreshGraph = !$scope.refreshGraph;
        }
      });
    };

    function getMinTickSize(statType){
      if(statType === 'week'){
        return [7, 'day'];
      }
      else{
        return [1, statType];
      }
    };
}])