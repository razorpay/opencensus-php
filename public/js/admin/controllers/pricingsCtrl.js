"use strict";
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
    $scope.itemList = [];
    $scope.networks = null;
    generateTable();

    var getDefaultRule = function() {
      return {
        payment_method: "card",
        payment_method_type: "",
        payment_network: "",
        payment_issuer: "",
        international: "0",
        amount_range_active: "0",
        amount_range: "low",
        amount_range_min: 0,
        amount_range_max: 0,
        percent_rate: 200,
        fixed_rate: 0
      };
    };

    function getPayload (input) {
      var data = input;
      // This contains high/low and we don't send that
      delete data.amount_range;
      if (data.amount_range_active == '0') {
        delete data.amount_range_min;
        delete data.amount_range_max;
      }
      return data;
    }

    $scope.new_plan = $scope.new_rule = getDefaultRule();

    $scope.createPlan = function () {
      $scope.new_plan = getDefaultRule();
      $scope.show_plan = {};
      $scope.create_plan = true;
    };

    var getNetworkList = function (method) {
      if ($scope.networks === null) {
        return {"":"All"};
      }

      var networks = {};

      switch (method) {
        // The networks object is differently indexed
        case 'netbanking':
          networks = $scope.networks.bank;
          break;
        // we copy over the card networks list to EMI networks list
        case 'emi':
          networks = $scope.networks.card;
          break;

        default:
          networks = $scope.networks[method];
      }

      networks[""] = "All";
      return networks;
    };

    $scope.getNetworks = function () {
      var request = $http({
        url: '/admin/networks' ,
        method: 'GET'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.networks = data.data;
          setPaymentNetworkList('new_rule', 'card');
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
     };

    $scope.savePlan = function () {

      var data = getPayload($scope.new_plan);

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
          angular.forEach(data.errors, function (value) {
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
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.$watch('new_rule.amount_range+new_rule.amount_range_active', function() {
      var range = getDefaultAmountRange($scope.new_rule);
      $scope.new_rule.amount_range_min = range[0];
      $scope.new_rule.amount_range_max = range[1];
    });

    $scope.$watch('new_plan.amount_range+new_plan.amount_range_active', function() {
      var range = getDefaultAmountRange($scope.new_plan);
      $scope.new_plan.amount_range_min = range[0];
      $scope.new_plan.amount_range_max = range[1];
    });

    // where is either new_rule or new_plan
    var setPaymentNetworkList = function(where) {
      var method = $scope[where].payment_method;
      $scope.itemList = getNetworkList(method);
      // This sets it to "All"
      $scope[where].payment_network = '';
    };

    $scope.$watch('new_rule.payment_method', function() {
      setPaymentNetworkList('new_rule');
    });

    $scope.$watch('new_plan.payment_method', function() {
      setPaymentNetworkList('new_plan');
    });

    $scope.saveRule = function () {

      var data = getPayload($scope.new_rule);
      var plan_id = $scope.show_plan.id;

      var request = $http({
        method: 'post',
        url: '/admin/pricing/' + plan_id,
        transformRequest: transformRequestAsFormPost,
        data: data
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success ', 'Rule added successfully', true);
          $scope.show_plan.rules.push(data.data);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
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
          $scope.show_plan = data.data;
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

    /**
     * Sets defaults ranges for now
     */
    function getDefaultAmountRange(input) {

      var range = [];
      if (input.amount_range == 'high') {
        range = [200000,1000000000];
      }
      else {
        range = [0,  200000];
      }

      return range;
    }

    // We fetch the networks on Load
    $scope.getNetworks();
  }
]);
