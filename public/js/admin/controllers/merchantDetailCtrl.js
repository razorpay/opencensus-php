// Merchant Details Controller
app.controller('MerchantDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', '$modal',
  function($scope, $http, $stateParams, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.merchant = {
      id: $stateParams.id
    };

    generateMerchant();

    $scope.lockForm = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/lock");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form locked successfully', true);
          $scope.merchant.details.locked = 1;
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

    $scope.unlockForm = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/unlock");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form unlocked successfully', true);
          $scope.merchant.details.locked = 0;
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

    $scope.activateMerchant = function(){

      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/activate");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Activated successfully', true);
          $scope.merchant.details.activated = 1;
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

    $scope.enableLive = function() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/enable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant enabled successfully', true);
          $scope.merchant.details.live = 1;
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

    $scope.disableLive = function() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/disable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant disabled successfully', true);
          $scope.merchant.details.live = 0;
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

    $scope.assignPricing = function(plan_id){
      var data = {
        pricing_plan_id: plan_id
      };


      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/pricing",
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Plan Assigned successfully', true);
          $scope.merchant.pricing_plan = data.data;
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

    $scope.assignTerminal = function(terminal){
      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/terminal",
                    transformRequest: transformRequestAsFormPost,
                    data: terminal
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Terminal Assigned successfully', true);
          terminal.id = data.data.id;
          terminal.created_at = data.data.created_at;
          $scope.merchant.terminals.items.push(terminal);
          $scope.merchant.terminals.count = $scope.merchant.terminals.count + 1;
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

    $scope.assignBanks = function(bankdata){
      var data = {banks: []};

      angular.forEach(bankdata, function(i,e) {
        if(i==true){
          data.banks.push(e);
        }
      });

      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/banks",
                    data: angular.toJson(data)
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Banks Assigned successfully', true);
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

    $scope.openAssignPricing = function () {
      var currentPlan = $scope.merchant.pricing_plan.id || "";

      var modalInstance = $modal.open({
        templateUrl: 'assignPricingModalContent.html',
        controller: 'assignPricingModalCtrl',
        resolve: {
          current: function() {
            return currentPlan;
          }
        }
      });

      modalInstance.result.then(
        function (plan_id) {
          $scope.assignPricing(plan_id);
        },
        function () {
          ;
        });
    };

    $scope.openAssignTerminal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'assignTerminalModalContent.html',
        controller: 'assignTerminalModalCtrl',
      });

      modalInstance.result.then(
        function (terminal) {
          $scope.assignTerminal(terminal);
        },
        function () {
          ;
        });
    };

    $scope.openAssignBanks = function () {
      var currentId = $scope.merchant.id;

      var modalInstance = $modal.open({
        templateUrl: 'assignBanksModalContent.html',
        controller: 'assignBanksModalCtrl',
        resolve: {
          current: function() {
            return currentId;
          }
        }
      });

      modalInstance.result.then(
        function (bankdata) {
          $scope.assignBanks(bankdata);
        },
        function () {
          ;
        });
    };


    function generateMerchant() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id);

      request
      .success(function(data){
        $scope.alerts.resetAlerts(true);

        if(data.success) {
          $scope.merchant = data.data;
          $scope.merchant.id = data.data.details.id;
          $scope.merchant.details.activation_progress = parseInt(($scope.merchant.details.steps_finished.length * 100)/ 5);
        }
        else {
          $scope.alerts.resetAlerts(true);
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.resetAlerts(true);
        $scope.alerts.addAlert('danger', null);
      });
    }
}])
.controller('assignPricingModalCtrl', ['$scope', '$modalInstance', '$http', 'current',
  function ($scope, $modalInstance, $http, current) {
      $scope.loading = true;

      $scope.pricing_plans = [];
      $scope.pricing_plan_id = current;

      var request = $http.get("/admin/pricing/list");
      request
        .success(function(data){
          if(data.success) {
            angular.forEach(data.data, function(value, key){
              $scope.pricing_plans.push({'id': value.id, 'name':value.name});
            });

            $scope.loading = false;
          }
        });

      $scope.ok = function (pricing_plan_id) {
        $modalInstance.close(pricing_plan_id);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('assignTerminalModalCtrl', ['$scope', '$modalInstance',
  function ($scope, $modalInstance) {
      $scope.ok = function (terminal) {
        $modalInstance.close(terminal);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('assignBanksModalCtrl', ['$scope', '$modalInstance', '$http', 'current',
  function ($scope, $modalInstance, $http, current) {
      $scope.loading = true;

      $scope.banks = [];
      $scope.bankdata = {};
      $scope.merchant_id = current;

      $scope.selectAllChange = function(value) {
        angular.forEach($scope.bankdata, function(i,e){
          $scope.bankdata[e] = value;
        });
      };

      var request = $http.get("/admin/merchant/" + current + "/banks");
      request
        .success(function(data){
          if(data.success) {
            $scope.banks = data.data;
            angular.forEach($scope.banks.enabled, function(key, value){
              $scope.bankdata[value] = true;
            });
            angular.forEach($scope.banks.disabled, function(key, value){
              $scope.bankdata[value] = false;
            });
            $scope.loading = false;
          }
        });

      $scope.ok = function (bankdata) {
        $modalInstance.close(bankdata);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}]);