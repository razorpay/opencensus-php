'use strict';
//Pricing List controller
app.controller('PricingsCtrl', [
  '$scope',
  '$stateParams',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$state',
  function(
    $scope,
    $stateParams,
    $http,
    alertsFactory,
    transformRequestAsFormPost,
    $state
  ) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.pricing_plans = {};
    $scope.show_plan = {};
    $scope.itemList = [];
    $scope.networks = null;

    var getDefaultRule = function() {
      return {
        feature: 'payment',
        payment_method: 'card',
        payment_method_type: '',
        payment_network: '',
        payment_issuer: '',
        international: '0',
        amount_range_active: '0',
        amount_range: '0-100000',
        amount_range_min: 0,
        amount_range_max: 0,
        percent_rate: 200,
        fixed_rate: 0,
      };
    };

    function getPayload(input) {
      var data = input;
      // This contains high/low and we don't send that
      delete data.amount_range;
      if (data.amount_range_active == '0') {
        delete data.amount_range_min;
        delete data.amount_range_max;
      }
      return data;
    }

    $scope.plan = {
      name: '',
      rules: [],
    };

    $scope.addRule = function addRule() {
      var new_rule = $scope.new_rule;

      $scope.new_rule = getDefaultRule();

      $scope.plan.rules.push(new_rule);
    };

    $scope.deleteRule = function deleteRule($index) {
      $scope.plan.rules.splice($index, 1);
    };

    $scope.new_plan = $scope.new_rule = getDefaultRule();

    var getNetworkList = function(method) {
      if ($scope.networks === null) {
        return { '': 'All' };
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
          networks = networks !== undefined ? networks : {};
      }

      networks[''] = 'All';
      return networks;
    };

    $scope.getNetworks = function() {
      var params = {
        route_name: 'pricing_supported_networks',
      };

      var request = $http.get('/admin/generic', {
        params: params,
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.networks = data.data;
            setPaymentNetworkList('new_rule', 'card');
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };

    $scope.savePlan = function() {
      var payload = { plan_name: $scope.plan.name, rules: [] };

      angular.forEach($scope.plan.rules, function(rule) {
        payload.rules.push(getPayload(rule));
      });

      var params = {
        route_name: 'pricing_create_plan',
        body: payload,
      };

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        data: params,
      });

      request
        .success(function(data) {
          if (data.success) {
            if (data.data.workflow_id) {
              $state.go('app.workflows.actions.detail', {
                action_id: data.data.id,
              });
            } else {
              $scope.alerts.addAlert(
                'success',
                'Plan created successfully',
                true
              );
              $state.go('app.pricingdetail', {
                id: data.data.id,
              });
            }
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };

    $scope.deletePricingPlanRule = function(ruleId) {
      var planId = $scope.show_plan.id;

      var params = {
        route_name: 'pricing_delete_plan_rule',
        url_params: {
          '{planId}': planId,
          '{ruleId}': ruleId,
        },
      };

      var request = $http({
        url: '/admin/generic',
        method: 'DELETE',
        params: params,
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Rule deleted successfully',
              true
            );
            $scope.show_plan.rules = $scope.show_plan.rules.filter(function(
              rule
            ) {
              return rule.id !== ruleId;
            });
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };

    $scope.$watch(
      'new_rule.amount_range+new_rule.amount_range_active',
      function() {
        var range = getDefaultAmountRange($scope.new_rule);
        $scope.new_rule.amount_range_min = range[0];
        $scope.new_rule.amount_range_max = range[1];
      }
    );

    $scope.$watch(
      'new_plan.amount_range+new_plan.amount_range_active',
      function() {
        var range = getDefaultAmountRange($scope.new_plan);
        $scope.new_plan.amount_range_min = range[0];
        $scope.new_plan.amount_range_max = range[1];
      }
    );

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

    $scope.saveRule = function() {
      var data = getPayload($scope.new_rule);
      var plan_id = $scope.show_plan.id;

      var params = {
        route_name: 'pricing_add_plan_rule',
        url_params: {
          '{id}': plan_id,
        },
        body: data,
      };

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        data: params,
      });

      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert('success ', 'Rule added successfully', true);
            $scope.show_plan.rules.push(data.data);
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });
    };

    $scope.showPlan = function(id) {
      if ($scope.show_plan.id == id) {
        $scope.show_plan = {};
        return;
      }

      var params = {
        route_name: 'pricing_get_plan',
        url_params: {
          '{id}': id,
        },
      };

      var request = $http.get('/admin/generic', {
        params: params,
      });

      request.success(function(data) {
        if (data.success) {
          if (data.data.length === 0) {
            $scope.alerts.addAlert(
              'danger',
              'No plan exists with the given id',
              true
            );
          }
          $scope.show_plan = data.data;
        }
      });
    };

    var pricing_plan_id = $stateParams.id;
    if (pricing_plan_id) {
      $scope.showPlan(pricing_plan_id);
    }

    $scope.generateTable = function() {
      var params = {
        route_name: 'pricing_get_merchant_plans',
      };

      var request = $http.get('/admin/generic', {
        params: params,
      });

      request.success(function(data) {
        if (data.success) {
          $scope.pricing_plans = data.data.items;
        }
      });
    };

    /**
     * Sets defaults ranges for now
     */
    function getDefaultAmountRange(input) {
      var range = [];
      if (input.amount_range == '0-200000') {
        range = [0, 200000];
      } else if (input.amount_range == '100000-200000') {
        range = [100000, 200000];
      } else if (input.amount_range == '200000-1000000000') {
        range = [200000, 1000000000];
      } else {
        range = [0, 100000];
      }

      return range;
    }

    // We fetch the networks on Load
    $scope.getNetworks();
  },
]);
