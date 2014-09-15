//Pricing List controller
app.controller('PricingsCtrl', ['$scope', '$http', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
  function($scope, $http, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {
    
    $scope.alerts=alertsFactory.getHandler();
    $scope.pricing_plans = {};
    $scope.show_plan = {};
    $scope.create_plan = false;


    generateTable();
    
    $scope.createPlan = function(){
      $scope.new_plan = {};
      $scope.show_plan = {};
      $scope.create_plan = true;
    }

    $scope.savePlan = function(){
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var data = $scope.new_plan;

      data._token = CSRF_TOKEN;

      var request = $http({
                    method: "post",
                    url: "/admin/pricing/new",
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Plan created successfully', true);
          $scope.create_plan = false;
          generateTable();
          $scope.showPlan(data.data.id);
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.saveRule = function(){
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var data = $scope.new_rule;
      var plan_id = $scope.show_plan.id;

      data._token = CSRF_TOKEN;

      var request = $http({
                    method: "post",
                    url: "/admin/pricing/" + plan_id,
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Rule added successfully', true);
          $scope.show_plan = {};
          $scope.showPlan(plan_id);
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.showPlan = function(id) {
      if($scope.show_plan.id == id) {
        $scope.show_plan={};
        return;
      }

      var request = $http.get("/admin/pricing/" + id);
      
      request
      .success(function(data){
        if(data.success) {
          $scope.create_plan = false;
          $scope.new_plan = {};
          $scope.show_plan = data.data;
          $scope.new_rule = {};
        }
      });
    }
    function generateTable() {
      $scope.alerts.addAlert('info', 'Processing...');

      var request = $http.get("/admin/pricing/list");
      
      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.resetAlerts(true);
          $scope.pricing_plans = data.data;
        }
      });
    }
}]);