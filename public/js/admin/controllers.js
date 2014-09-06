'use strict';

/* Controllers */

angular.module('app.controllers', ['pascalprecht.translate', 'ngCookies'])
  .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window',
    function(              $scope,   $translate,   $localStorage,   $window) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');

      // config
      $scope.app = {
        name: 'RZP Admin',
        version: '0.9.1',
        today: new Date(),
        // for chart colors
        color: {
          primary: '#7266ba',
          info:    '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger:  '#f05050',
          light:   '#e8eff0',
          dark:    '#3a3f51',
          black:   '#1c2b36'
        },
        settings: {
          themeID: 9,
          navbarHeaderColor: 'bg-dark',
          navbarCollapseColor: 'bg-primary',
          asideColor: 'bg-dark',
          headerFixed: true,
          asideFixed: true,
          asideFolded: false
        }
      }

      // save settings to local storage
      if ( angular.isDefined($localStorage.settings) ) {
        $scope.app.settings = $localStorage.settings;
      } else {
        $localStorage.settings = $scope.app.settings;
      }
      $scope.$watch('app.settings', function(){ $localStorage.settings = $scope.app.settings; }, true);

      // angular translate
      $scope.langs = {en:'English'};
      $scope.selectLang = $scope.langs[$translate.proposedLanguage()] || "English";
      $scope.setLang = function(langKey) {
        // set the current lang
        $scope.selectLang = $scope.langs[langKey];
        // You can change the language during runtime
        $translate.use(langKey);
      };


      function isSmartDevice( $window )
      {
          // Adapted from http://www.detectmobilebrowsers.com
          var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
          // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
          return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
      }

      $scope.flag = false;

  }])

  //Signin Controller
  .controller('SigninCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'admin', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, $state, $stateParams, alertsFactory, admin, transformRequestAsFormPost, CSRF_TOKEN) {
      $scope.data = {
        _token: CSRF_TOKEN
      }

      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      if($stateParams.username){
        $scope.data.username = $stateParams.username;
      }

      $scope.submit = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
          return false;     
        }

        $scope.alerts.addAlert('info', 'Processing...', true);

        var request = $http({
                    method: "post",
                    url: "/admin/signin",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  admin.identity(true);
                  $state.go('app.dashboard');
                }
                else {
                  $scope.alerts.resetAlerts();
                  angular.forEach(data.errors, function(error, key) {
                  $scope.alerts.addAlert('danger', error);
                  });      
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger', null, true);
              })
      };
  }])

  //Admin profile Controller
  .controller('AdminCtrl', ['$scope', '$http', '$state', 'admin', 'CSRF_TOKEN', '$modal', 'alertsFactory', '$idle', '$keepalive',
    function($scope, $http, $state, admin, CSRF_TOKEN, $modal, alertsFactory, $idle, $keepalive) {
      
      admin.identity().then(function(data){
        $scope.admin = data;
      });

      $scope.alerts = alertsFactory.getHandler();

      $scope.logout = function() {
        logoutRequest()
          .finally(function() {
                  $state.go('access.signin');  
          });
      };

      var passwordModalCtrl = function ($scope, $modalInstance) {
        $scope.ok = function (data) {
          $modalInstance.close(data);
        };
        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
      };

      $scope.changePassword = function () {
        var modalInstance = $modal.open({
          templateUrl: 'passwordModalContent.html',
          controller: passwordModalCtrl
        });

        modalInstance.result.then(
          function (data) {
            passwordChangeRequest(data);
          },
          function () {
            ;
          });
      };

      function logoutRequest(){
        $scope.data = {
          _token: CSRF_TOKEN
        }

        var request = $http({
            method: "get",
            url: "/admin/user/logout",
            data: $scope.data
        });

        request
          .finally(function() {
                admin.identity(true);
          });

        return request;
      }
      function passwordChangeRequest(data) {
        $scope.alerts.addAlert('info', 'Processing...', true);

        data._token = CSRF_TOKEN;

        var request = $http({
          method: "post",
          url: "/admin/password",
          data: data
        });

        request
        .success(function(data){
          if(data.success){
            $scope.alerts.addAlert('success', 'Password changed successfully.', true);
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

      function closeModals() {
        if ($scope.warning) {
          $scope.warning.close();
          $scope.warning = null;
        }

        if ($scope.timedout) {
          $scope.timedout.close();
          $scope.timedout = null;
        }
      }

      $scope.$on('$idleStart', function() {
        closeModals();

        $scope.warning = $modal.open({
          templateUrl: 'warning-dialog.html',
          windowClass: 'modal-danger'
        });
      });

      $scope.$on('$idleEnd', function() {
        closeModals();
      });

      $scope.$on('$idleTimeout', function() {
        logoutRequest().finally(function(){
          $state.go('lockme', { "username": $scope.admin.username}).finally(function(){
            closeModals();
          });
        });

      });
  }])
  //Merchant List controller
  .controller('MerchantsCtrl', ['$scope', '$http',
    function($scope, $http) {

    //Aggreagates
    $scope.merchants = {};

    generateTable();
    
    function generateTable() {
      if($scope.pending){
        var request = $http.get("/admin/merchant/list?pending=true");
      }
      else{
        var request = $http.get("/admin/merchant/list");
      }

      request
      .success(function(data){
        if(data.success) {
          $scope.merchants = data.data;
        }
      });
    }
  }])
  .controller('MerchantDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost', '$modal',
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

      var assignTerminalModalCtrl = function ($scope, $modalInstance) {
        $scope.ok = function (terminal) {
          $modalInstance.close(terminal);
        };

        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
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
  }])
  
  .controller('MerchantActivationCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory',
    function($scope, $http, $stateParams, alertsFactory){
      $scope.alerts = alertsFactory.getHandler();

      $scope.merchant = {
        id: $stateParams.id
      };

      $scope.check = {};

      $scope.data = {
        1: {},
        2: {},
        3: {},
        4: {},
        5: {},
        6: {}
      };

      $scope.files = {};

      $scope.locked = true;

      getData();

      function getData() {
        var request = $http.get('/admin/merchant/'+$scope.merchant.id+'/details');

        request.success(function(data){
          if(data.success){
            angular.forEach(data.data.merchant.steps_finished, function(value, key) {
              $scope.check[value] = true;
            });

            angular.forEach(data.data.activation.data, function(value, key){
              $scope.data[key] = value;       
            });

            angular.forEach(data.data.activation.files, function(value, key){
              $scope.files[key] = value;
            });

            $scope.merchant = data.data.merchant;
          
          }
          else {
            $scope.alerts.addAlert('danger');
          }
        })
        .error(function(){
          $scope.alerts.addAlert('danger');
        });
      };
  }])
  //Pricing List controller
  .controller('PricingsCtrl', ['$scope', '$http', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
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
        var request = $http.get("/admin/pricing/list");
        
        request
        .success(function(data){
          if(data.success) {
            $scope.pricing_plans = data.data;
          }
        });
      }
  }])
  
  //Admin List controller
  .controller('AdminsCtrl', ['$scope', '$http', '$modal', 'admin', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
    function($scope, $http, $modal, admin, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {

    $scope.admins = {};

    $scope.alerts = alertsFactory.getHandler();

    admin.identity().then(function(data){
      $scope.admin = data;
    });

    generateTable();
    
    $scope.delete = function(id) {
      $scope.alerts.addAlert('info', 'Processing...', true);

      var request = $http.get("/admin/users/" + id + "/delete?_token="+CSRF_TOKEN);

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', "Admin deleted successfully", true);

          generateTable();
        }
        else {
          $scope.alerts.resetAlerts();
          angular.foreach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.createAdmin = function () {
      var modalInstance = $modal.open({
        templateUrl: 'newAdminModalContent.html',
        controller: newAdminModalCtrl,
        size: 'lg'
      });

      modalInstance.result.then(
        function (data) {
          console.log(data);
          newAdminRequest(data);
        },
        function () {
          ;
        });
    };

    var newAdminModalCtrl = function ($scope, $modalInstance) {
      $scope.ok = function (data) {
        $modalInstance.close(data);
      };
      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };

    function newAdminRequest(data){
      $scope.alerts.addAlert('info', 'Processing...', true);

      data._token = CSRF_TOKEN;

      var request = $http({
                    method: "post",
                    url: "/admin/users/add",
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Admin created successfully', true);
          generateTable();
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

    function generateTable() {
      var request = $http.get("/admin/users");

      request
      .success(function(data){
        if(data.success) {

          $scope.admins = data.data;
        }
      });
    }
  }])
  ;