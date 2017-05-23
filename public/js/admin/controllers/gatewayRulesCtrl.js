'use strict';
//Pricing List controller
app
  .controller('GatewayRulesCtrl', [
    '$scope',
    '$http',
    'alertsFactory',
    '$modal',
    'admin',
    'utilMapping',
    'transformRequestAsFormPost',
    function(
      $scope,
      $http,
      alertsFactory,
      $modal,
      admin,
      utilMapping,
      transformRequestAsFormPost
    ) {
      $scope.mode = 'live';
      $scope.entity_type = 'gateway_rule';
      $scope.alerts = alertsFactory.getHandler();

      $scope.networkMap = utilMapping.getMap('networkMap');
      $scope.methodMap = utilMapping.getMap('methodMap');
      $scope.gatewayAcquirerMap = utilMapping.getMap('gatewayAcquirerMap');

      $scope.merchantId = '100000Razorpay'; // Default search for shared merchant

      admin.identity().then(function(data) {
        $scope.admin = data;
      });

      // fetch gateway rules on basis of mode and merchant id selected by user
      $scope.getRulesById = function() {
        var data = {
          route_name: 'admin_fetch_entity_multiple',
          url_params: {
            '{type}': 'gateway_rule',
          },
          mode: $scope.mode,
          query_params: {
            merchant_id: $scope.merchantId,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.gatewayRules = data.data.items;

              if (!$scope.gatewayRules.length) {
                $scope.noResults = true;
              } else {
                $scope.noResults = false;
              }
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      };

      $scope.getRulesById();

      function cleanRuleInfo(rule) {
        var gatewayRule = Object.assign({}, rule);

        Object.keys(gatewayRule).forEach(function(key) {
          gatewayRule.issuer = gatewayRule.issuer === 'ALL'
            ? null
            : gatewayRule.issuer;

          if (!gatewayRule[key]) {
            delete gatewayRule[key];
          }
        });
        return gatewayRule;
      }

      // Create a new gateway rule
      $scope.createRule = function(rule) {
        var body = cleanRuleInfo(rule);

        var request = $http({
          url: 'admin/generic',
          method: 'POST',
          params: {
            route_name: 'gateway_create_rule',
            mode: $scope.mode,
          },
          data: {
            body: body,
          },
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.getRulesById();
              $scope.alerts.addAlert(
                'success',
                'Gateway rule is successfully created',
                true
              );
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

      // Update gateway rule by id
      $scope.updateRuleById = function(rule) {
        var request = $http({
          url: 'admin/generic',
          method: 'patch',
          params: {
            route_name: 'gateway_update_rule',
            mode: $scope.mode,
            url_params: {
              '{id}': rule.id,
            },
          },
          data: {
            body: {
              load: rule.load,
            },
          },
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Gateway rule: ' + data.data.id + ' is successfully updated',
                true
              );
              $scope.getRulesById();
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

      // Delete gateway rule by id
      $scope.deleteRuleById = function(gatewayId) {
        var params = {
          route_name: 'gateway_delete_rule',
          mode: $scope.mode,
          url_params: {
            '{id}': gatewayId,
          },
        };

        var request = $http({
          url: '/admin/generic',
          method: 'delete',
          params: params,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.getRulesById();
              $scope.alerts.addAlert(
                'success',
                'Gateway rule: ' + data.data.id + ' is successfully deleted',
                true
              );
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

      // Invokes modal to edit existing gateway rule
      $scope.editRule = function(gatewayRule) {
        openRuleModal(gatewayRule); // edit existing rule with editMode = true
      };

      // Invokes modal to create new gateway rule
      $scope.addNewRule = function() {
        openRuleModal({}); // create new rule = {}
      };

      // Open modal for editing existing gateway rule OR creating new gateway rule
      function openRuleModal(gatewayRule) {
        var modalInstance = $modal.open({
          templateUrl: 'editGatewayRuleModal.html',
          controller: 'editGatewayRuleModalCtrl',
          backdrop: 'static',
          resolve: {
            current: function() {
              return jQuery.extend({}, gatewayRule);
            },
          },
        });
        modalInstance.result.then(function(currentRule) {
          if (Object.keys(gatewayRule).length) {
            $scope.updateRuleById(currentRule);
          } else {
            $scope.createRule(currentRule);
          }
        }, $.noop);
      }
    },
  ])
  .controller('editGatewayRuleModalCtrl', [
    '$scope',
    '$http',
    '$modalInstance',
    'admin',
    'current',
    'utilMapping',
    function($scope, $http, $modalInstance, admin, current, utilMapping) {
      $scope.current = current;
      $scope.networkMap = utilMapping.getMap('networkMap');
      $scope.methodMap = utilMapping.getMap('methodMap');
      $scope.gatewayAcquirerMap = utilMapping.getMap('gatewayAcquirerMap');

      $scope.editMode = false;

      admin.identity().then(function(data) {
        $scope.admin = data;
      });

      if (Object.keys(current).length) {
        $scope.editMode = true;
      }

      function fetchBanks() {
        var data = {
          route_name: 'merchant_get_banks',
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request.success(function(data) {
          if (data.success) {
            $scope.banks = data.data;
            console.log($scope.banks);
          }
        });
      }
      fetchBanks();

      $scope.ok = function(currentRule) {
        $modalInstance.close(currentRule);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
