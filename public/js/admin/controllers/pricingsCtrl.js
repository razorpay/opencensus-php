//Pricing List controller
app.controller('PricingsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.pricing_plans = {};
    $scope.show_plan = {};
    $scope.create_plan = false;
    generateTable();
    $scope.createPlan = function () {
      $scope.new_plan = {};
      $scope.show_plan = {};
      $scope.create_plan = true;
    };
    $scope.savePlan = function () {
      var data = $scope.new_plan;
      assignRangeForCreate(data);
      var request = $http({
        method: 'post',
        url: '/admin/pricing/new',
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Plan created successfully', true);
          $scope.create_plan = false;
          $scope.pricing_plans.push(data.data);
          $scope.showPlan(data.data.id);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.deletePricingPlanRule = function (ruleId)
    {
      var planId = $scope.show_plan.id;

      var request = $http({
        method: 'delete',
        url: '/admin/pricing/'+planId+'/rules/' + ruleId
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Rule deleted successfully', true);
          $scope.show_plan.rules =
            $scope.show_plan.rules.filter(function(rule) {
              return rule.id !== ruleId;
            }
          );
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.saveRule = function () {
      var data = $scope.new_rule;
      var plan_id = $scope.show_plan.id;
      assignRangeForCreate(data);
      var request = $http({
        method: 'post',
        url: '/admin/pricing/' + plan_id,
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success ', 'Rule added successfully', true);
          assignRangeForRule(data.data);
          $scope.show_plan.rules.push(data.data);
          $scope.new_rule = {};
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.showPlan = function (id) {
      if ($scope.show_plan.id == id) {
        $scope.show_plan = {};
        return;
      }
      var request = $http.get('/admin/pricing/' + id);
      request.success(function (data) {
        if (data.success) {
          $scope.create_plan = false;
          $scope.new_plan = {};
          assignRangeForShowPlan(data.data);
          $scope.show_plan = data.data;
          $scope.new_rule = {};
        }
      });
    };
    function generateTable() {
      var request = $http.get('/admin/pricing/list');
      request.success(function (data) {
        if (data.success) {
          $scope.pricing_plans = data.data;
        }
      });
    }
    function assignRangeForShowPlan(show_plan) {
      for (var i = show_plan.rules.length - 1; i >= 0; i--) {
          assignRangeForRule(show_plan.rules[i]);
      };
    }
    function assignRangeForRule(show_rule){
      if(show_rule.amount_range_active){
        if(show_rule.amount_range_max === 200000){
          show_rule.amount_range = 0;
        }else if (show_rule.amount_range_min === 200000) {
          show_rule.amount_range = 1;
        }else{
          show_rule.amount_range = null;
        };
      };
    }
    function assignRangeForCreate(create_rule){
      if(create_rule.amount_range === "1"){
        create_rule.amount_range_min = 200000;
        create_rule.amount_range_max = 1000000000;
      }else if (create_rule.amount_range === "0") {
        create_rule.amount_range_min = 0;
        create_rule.amount_range_max = 200000;
      }else{

      }

      //Unset amount_range
      delete create_rule['amount_range'];
    }
  }
]);
