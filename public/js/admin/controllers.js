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

  //User profile Controller
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

      $scope.changePassword = function (size) {
        var modalInstance = $modal.open({
          templateUrl: 'passwordModalContent.html',
          controller: passwordModalCtrl,
          size: size
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
            url: "/admin/logout",
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
      var request = $http.get("/admin/merchant/list");

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

      var assignPricingModalCtrl = function ($scope, $modalInstance, pricing_plans) {
        $scope.pricing_plans = pricing_plans;
        console.log($scope.pricing_plans);
        $scope.ok = function (pricing_plan_id) {
          $modalInstance.close(pricing_plan_id);
        };

        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
      };

      $scope.openAssignPricing = function () {

        var pricing_plans = getPricingPlans();

        var modalInstance = $modal.open({
          templateUrl: 'assignPricingModalContent.html',
          controller: assignPricingModalCtrl,
          size: 'lg',
          resolve: {
            pricing_plans: function () {
              return pricing_plans;
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
            $scope.merchant = data.details;

            $scope.merchant.pricing_plan = data.pricing_plan;
            $scope.merchant.terminal = data.terminal;
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

  

  //Single Transaction Details controller
  .controller('TransactionDetailCtrl', ['$scope', '$http', '$stateParams', 'modeFactory', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
    function($scope, $http, $stateParams, modeFactory, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.transaction = {
        id: $stateParams.id
      };

      fetchTransaction();

      $scope.getStatusClass = function(status) {
        var mapper = {
          open: "bg-light",
          authorized: "bg-info",
          captured: "bg-success",
          refunded: "bg-warning",
          failed: "bg-danger"
        }
        return mapper[status];
      }

      $scope.capture = function(amount) {

        var captureAmount = parseInt(amount);

        if(!captureAmount){
          $scope.alerts.addAlert('danger', 'Invalid capture amount', true);
          return;
        }

        $scope.alerts.addAlert('info', 'Processing... ', true);

        var data = {
          _token: CSRF_TOKEN,
          amount: captureAmount
        }

        var request = $http({
                      method: "post",
                      url: "/" + modeFactory.getMode() + "/transactions/"  + $scope.transaction.id + "/capture",
                      transformRequest: transformRequestAsFormPost,
                      data: data
                  });

        request.success(function(data){
          if(data.success){
            $scope.alerts.addAlert('success', "Transaction Captured", true);
            $scope.transaction.status = "captured";
            $scope.transaction.amount = captureAmount;
          }
          else {
            $scope.alerts.addAlert('danger', null, true);
          }
        })
        .error(function(){
          $scope.alerts.addAlert('danger', null, true);
        })
      };

      $scope.refund = function() {
        $scope.alerts.addAlert('info', 'Processing... ', true);
        var data = {
          _token: CSRF_TOKEN
        }

        var request = $http({
                      method: "post",
                      url: "/" + modeFactory.getMode() + "/transactions/"  + $scope.transaction.id + "/refund",
                      transformRequest: transformRequestAsFormPost,
                      data: data
                  });

        request.success(function(data){
          if(data.success) {
            $scope.alerts.addAlert('success', "Transaction Refunded", true);
            $scope.transaction.status = "refunded";
          }
          else {
            $scope.alerts.addAlert('danger', null, true);
          }
        })
        .error(function(){
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('danger', null, true);
        })
      };

      function fetchTransaction() {
        $scope.alerts.addAlert('info', 'Processing...', true);
    
        var request = $http.get("/" + modeFactory.getMode() +  "/transactions/" + $scope.transaction.id);

        request
        .success(function(data) {
          $scope.alerts.resetAlerts();

          if(data.success) {
            $scope.transaction = data.data[0];
          }
          else {
            angular.forEach(data.errors, function(error, key) {
              $scope.alerts.addAlert('danger', error);
            });      
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        }); 
      };
  }])
  
  //Capture Modal Box Controller
  .controller('CaptureModalCtrl', ['$scope', '$modal', '$log', 
    function($scope, $modal, $log) {
      var ModalInstanceCtrl = function ($scope, $modalInstance, amount) {
        $scope.amount = amount;
        $scope.ok = function (amount) {
          $modalInstance.close(amount);
        };

        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
      };

      $scope.open = function (size) {
        var modalInstance = $modal.open({
          templateUrl: 'captureModalContent.html',
          controller: ModalInstanceCtrl,
          size: size,
          resolve: {
            amount: function () {
              return $scope.transaction.amount;
            }
          }
        });

        modalInstance.result.then(
          function (amount) {
            $scope.capture(amount);
          },
          function () {
            ;
          });
      };
  }])

  //API keys listing and rolling controller
  .controller('KeysCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost', '$modal',
    function($scope, $http, modeFactory, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost, $modal){
      $scope.mode = modeFactory.getMode();
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.keys = {
        data: {},
        count: 0
      };

      fetchKeys();
      
      $scope.generateKey = function(){
        $scope.alerts.addAlert('info', "Processing..", true);

        var data = {
          _token: CSRF_TOKEN
        }

        var request = $http({
                      method: "post",
                      url: "/" + modeFactory.getMode() + "/key/new",
                      transformRequest: transformRequestAsFormPost,
                      data: data
        });

        request
        .success(function(data){
          if(data.success){
            $scope.alerts.addAlert('success', "Key Generated", true);
            $scope.openNewKey('lg', {id: data.data.id, secret:data.data.secret});
          }
          else {
            $scope.alerts.addAlert('danger', null, true);
          }
        })
        .error(function(){
          $scope.alerts.addAlert('danger', null, true);
        });
      };

      function fetchKeys(){
        $scope.alerts.addAlert('info', "Processing..", true);

        var request = $http.get('/'+$scope.mode+'/keys');

        request
        .success(function(data){
          $scope.alerts.resetAlerts();
          if(data.success) {
            $scope.keys.count = data.count;
            $scope.keys.data = data.data;
          }
          else {
            $scope.alerts.addAlert('danger');
          }
        })
        .error(function(){
          $scope.alerts.addAlert('danger', null, true);
        })
      }

      $scope.rollKey = function(data) {
        var key_id = data[0];
        var delay_roll = parseInt(data[1]);

        if(!key_id) {
          $scope.alerts.addAlert('danger', null, true);
          return;
        };

        $scope.alerts.addAlert('info', 'Processing... ', true);
        var data = {
          _token: CSRF_TOKEN,
          id: key_id,
          delay_roll: delay_roll
        }

        var request = $http({
                      method: "post",
                      url: "/" + modeFactory.getMode() + "/keys",
                      transformRequest: transformRequestAsFormPost,
                      data: data
                  });

        request.success(function(data){
          if(data.success) {
            $scope.alerts.addAlert('success', "Key Rolled", true);
            $scope.openNewKey('lg', {id: data.key_id, secret:data.secret});
          }
          else {
            $scope.alerts.addAlert('danger', null, true);
          }
        })
        .error(function(){
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('danger', null, true);
        })
      };

      

      var rollKeyModalCtrl = function ($scope, $modalInstance, key_id) {
        $scope.delay_roll = 1;
        $scope.ok = function (delay_roll) {
          $modalInstance.close([key_id, delay_roll]);
        };

        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
      };

      $scope.openRollKey = function (size, key_id) {
        var modalInstance = $modal.open({
          templateUrl: 'rollKeyModalContent.html',
          controller: rollKeyModalCtrl,
          size: size,
          resolve: {
            key_id: function () {
              return key_id;
            }
          }
        });

        modalInstance.result.then(
          function (data) {
            $scope.rollKey(data);
          },
          function () {
            ;
          });
      };

      var newKeyModalCtrl = function ($scope, $modalInstance, key) {
        $scope.key = key;
        $scope.ok = function () {
          $modalInstance.close();
        };
      };

      $scope.openNewKey = function (size, key) {
        var modalInstance = $modal.open({
          templateUrl: 'newKeyModalContent.html',
          controller: newKeyModalCtrl,
          backdrop: 'static',
          size: size,
          resolve: {
            key: function () {
              return key;
            }
          }
        });

        modalInstance.result.then(
          function () {
            fetchKeys();
          },
          function () {
            ;
          });
      };
  }])

  //Activation Form Controller
  .controller('ActivationCtrl', ['$scope', '$http', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost', '$upload', 'user',
    function($scope, $http, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost, $upload, user){
      $scope.steps={
        percent:0,
        step1:true
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

      $scope.formAlerts = alertsFactory.getHandler();

      $scope.alerts = {
        1: alertsFactory.getHandler(),
        2: alertsFactory.getHandler(),
        3: alertsFactory.getHandler(),
        4: alertsFactory.getHandler(),
        5: alertsFactory.getHandler(),
        6: alertsFactory.getHandler()
      };

      $scope.fileAlerts = {
        bussiness_proof: alertsFactory.getHandler(),
        bussiness_pan_proof: alertsFactory.getHandler(),
        promoter_pan_proof: alertsFactory.getHandler(),
        address_proof: alertsFactory.getHandler()
      };


      $scope.submit = function(step) {
        if(step !== 6){
          saveStep(step);
        }
        else {
          submitForm(step);
        }
      };

      $scope.onFileSelect = saveFile;

      $scope.genOperation = function(flag) {
        if(flag) {
          $scope.data[2].bussiness_operation_address = $scope.data[2].bussiness_registered_address;
          $scope.data[2].bussiness_operation_state = $scope.data[2].bussiness_registered_state;
          $scope.data[2].bussiness_operation_city = $scope.data[2].bussiness_registered_city;
          $scope.data[2].bussiness_operation_pin = $scope.data[2].bussiness_registered_pin;
        }
      };

      getData();
      
      function getData() {
        var request = $http.get('/activation/details');

        request.success(function(data){
          var steps_finished = JSON.parse(data.steps_finished);

          angular.forEach(steps_finished, function(value, key) {
            $scope.check[value] = true;
          });

          angular.forEach(data.data, function(value, key){
            $scope.data[key] = value;       
          });

          angular.forEach(data.files, function(value, key){
            $scope.fileAlerts[key].addAlert('success', 'File already uploaded');
          });

          if(parseInt(data.submitted)) {
            user.identity().then(function(data){
              if(parseInt(data.activated)) {
                $scope.formAlerts.addAlert('info', 'User is already live');
              }
              else 
                $scope.formAlerts.addAlert('info', 'Form has been submitted for activation and is pending admin response');
            });
          }

          if(parseInt(data.locked)) {
            $scope.locked = true;
            $scope.formAlerts.addAlert('warning', 'Form has been locked by admin, changes are not allowed.');
          }
        });
      };

      function saveStep(step){
        $scope.alerts[step].addAlert('info', 'Processing...', true);

        var data = {
          _token: CSRF_TOKEN,
        };

        angular.forEach($scope.data[step], function(value, key) {
          data[key] = value;
        });

        var request = $http({
                      method: "post",
                      url: "/activation/save/step/" + step,
                      transformRequest: transformRequestAsFormPost,
                      data: data
        });

        request
        .success(function(data){
          if(data.success) {
            $scope.alerts[step].addAlert('success', 'Step Saved Successfully', true);
            $scope.check[step] = true;
          }
          else {
            $scope.alerts[step].resetAlerts();
            angular.forEach(data.errors, function(value, key){
              $scope.alerts[step].addAlert('danger', value);
            });
            $scope.check[step] = false;
          }
        })
        .error(function(){
          $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
          $scope.check[step] = false;
        })
      };

      function saveFile($files, fieldname) {
        var file = $files[0];
        
        $scope.locked = true;

        $scope.fileAlerts[fieldname].addAlert('info', 'Uploading...', true);

        var request = $upload.upload({
          url: '/activation/save/file',
          method: 'POST',
          data: {_token: CSRF_TOKEN},
          file: file, 
          fileFormDataName: fieldname,
          formDataAppender: function(fd, key, val) {
            if (angular.isArray(val)) {
                angular.forEach(val, function(v) {
                    fd.append(key, v);
                });
            } else {
                fd.append(key, val);
            }
          },
        });

        request
        .progress(function(evt) {
          console.log('percent: ' + parseInt(100.0 * evt.loaded / evt.total));
        })
        .success(function(data, status, headers, config) {
          if(data.success){
            $scope.fileAlerts[fieldname].addAlert('success', 'File Uploaded Successfully', true);
          }
          else{
            $scope.fileAlerts[fieldname].resetAlerts();
            angular.forEach(data.errors, function(value, key){
              $scope.fileAlerts[fieldname].addAlert('danger', value);
            });
          }
        })
        .error(function(){
          $scope.fileAlerts[fieldname].addAlert('danger', 'File upload failed.', true);
        })
        .finally(function(){
          $scope.locked = false;
        });

      };

      function submitForm(step){
        $scope.alerts[step].addAlert('info', 'Processing...', true);

        if($scope.data[6].agree_terms !== true) {
          $scope.alerts[step].addAlert('danger', 'You must agree to the terms & conditions to use Razorpay services', true);
          return;
        }

        var data = {
          _token: CSRF_TOKEN,
        };

        angular.forEach($scope.data[step], function(value, key){
          data[key] = value;
        });

        var request = $http({
                      method: "post",
                      url: "/activation",
                      transformRequest: transformRequestAsFormPost,
                      data: data
        });

        request
        .success(function(data){
          if(data.success) {
            $scope.alerts[step].addAlert('success', 'Form submitted Successfully!', true);
            $scope.check[step] = true;
            getData();
          }
          else {
            $scope.alerts[step].resetAlerts();
            angular.forEach(data.errors, function(value, key){
              $scope.alerts[step].addAlert('danger', value);
            });
            $scope.check[step] = false;
          }
        })
        .error(function(){
          $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
          $scope.check[step] = false;
        })
      };
  }])
  ;