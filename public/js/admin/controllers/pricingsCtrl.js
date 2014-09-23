//Pricing List controller
app.controller('PricingsCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost',
  function($scope, $http, alertsFactory, transformRequestAsFormPost) {
    
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
      var data = $scope.new_plan;

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
          $scope.pricing_plans.push(data.data);
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
      var data = $scope.new_rule;
      var plan_id = $scope.show_plan.id;

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
          $scope.show_plan.rules.push(data.data);
          $scope.new_rule = {};
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
      var request = $http.get("/admin/pricing/list");
      
      request
      .success(function(data){
        if(data.success) {
          $scope.pricing_plans = data.data;
        }
      });
    }
}]);