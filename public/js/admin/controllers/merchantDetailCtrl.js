//Merchant Details Controller
app.controller('MerchantDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost', '$modal',
  function($scope, $http, $stateParams, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.merchant = {
      id: $stateParams.id
    };

    generateMerchant();
    
    $scope.lockForm = function(){
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/lock?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form Locked', true);
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/unlock?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form Unlocked', true);
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/activate?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Activated successfully', true);
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/enable?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant enabled successfully', true);
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/disable?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant disabled successfully', true);
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var data = {
        _token: CSRF_TOKEN,
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
          generateMerchant();
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
      $scope.alerts.addAlert('info', 'Processing...', true);
      
      var data = terminal;

      data._token = CSRF_TOKEN;

      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/terminal",
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Terminal Assigned successfully', true);
          generateMerchant();
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

      var pricing_plans = getPricingPlans();

      var currentPlan = $scope.merchant.pricing_plan.id || "";

      var modalInstance = $modal.open({
        templateUrl: 'assignPricingModalContent.html',
        controller: assignPricingModalCtrl,
        size: 'lg',
        resolve: {
          pricing_plans: function () {
            return pricing_plans;
          },
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
        controller: assignTerminalModalCtrl,
        size: 'lg'
      });

      modalInstance.result.then(
        function (terminal) {
          $scope.assignTerminal(terminal);
        },
        function () {
          ;
        });
    };

    var assignPricingModalCtrl = function ($scope, $modalInstance, pricing_plans, current) {
      $scope.pricing_plans = pricing_plans;
      $scope.pricing_plan_id = current;
      $scope.ok = function (pricing_plan_id) {
        $modalInstance.close(pricing_plan_id);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };

    var assignTerminalModalCtrl = function ($scope, $modalInstance) {
      $scope.ok = function (terminal) {
        $modalInstance.close(terminal);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };


    function getPricingPlans(){
      var plans = [];

      $scope.alerts.addAlert('info', 'Processing...', true);

      var request = $http.get("/admin/pricing/list");

      request
      .success(function(data){
        $scope.alerts.resetAlerts();
        if(data.success) {
          angular.forEach(data.data, function(value, key){
            plans.push({'id': value.id, 'name':value.name});
          })
        }
        else {
          $scope.alerts.addAlert('danger');
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      })
      return plans;
    };

    function generateMerchant() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id);

      request
      .success(function(data){
        if(data.success) {
          $scope.merchant = data.data.details;

          $scope.merchant.pricing_plan = data.data.pricing_plan;
          $scope.merchant.terminal = data.data.terminal;
          $scope.merchant.activation_progress = parseInt(($scope.merchant.steps_finished.length * 100)/ 6);
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    }
}]);