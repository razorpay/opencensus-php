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
          $scope.alerts.addAlert('success', 'Merchant Form locked successfully', true);
          $scope.merchant.locked = 1;
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
          $scope.alerts.addAlert('success', 'Merchant Form unlocked successfully', true);
          $scope.merchant.locked = 0;
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
          $scope.merchant.activated = 1;
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
          $scope.merchant.live = 1;
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
          $scope.merchant.live = 0;
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
          $scope.merchant.terminal = data.data;
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

    function generateMerchant() {
      $scope.alerts.addAlert('info', 'Processing...');
      var request = $http.get("/admin/merchant/"+$scope.merchant.id);

      request
      .success(function(data){
        $scope.alerts.resetAlerts(true);
        
        if(data.success) {
          $scope.merchant = data.data.details;

          $scope.merchant.pricing_plan = data.data.pricing_plan;
          $scope.merchant.terminal = data.data.terminal;
          $scope.merchant.activation_progress = parseInt(($scope.merchant.steps_finished.length * 100)/ 6);
        }
        else {
          $scope.alerts.resetAlerts(true);
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', key + ':' + value);
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
}]);