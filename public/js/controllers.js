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
        name: 'Razorpay',
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
      $scope.langs = {en:'English', hi:'Hindi'};
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
  .controller('SigninCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'user', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, $state, $stateParams, alertsFactory, user, transformRequestAsFormPost, CSRF_TOKEN) {
      $scope.data = {
        _token: CSRF_TOKEN
      }

      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      if($stateParams.email){
        $scope.data.email = $stateParams.email;
      }

      $scope.submit = function($valid) {
        if(!$valid)  {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
          return false;     
        }

        $scope.alerts.addAlert('info', 'Processing...', true);

        var request = $http({
                    method: "post",
                    url: "/user/signin",
                    transformRequest: transformRequestAsFormPost,
                    data: $scope.data
                });

        request
              .success(function(data) {
                if(data.success) {
                  user.identity(true);
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

  //Registration Controller
  .controller('RegisterCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
      
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.data = {
        _token: CSRF_TOKEN
      }

      $scope.agree = false;

      $scope.submit = function($valid) {
          if(!$valid)  {
            $scope.alerts.addAlert('danger', 'Please fill all the fields', true);     
            return true;     
          }

          if(!$scope.agree) {
            $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);     
            return true;
          }

          $scope.alerts.addAlert('info', 'Processing...', true);

          var request = $http({
                      method: "post",
                      url: "/user/register",
                      transformRequest: transformRequestAsFormPost,
                      data: $scope.data
                  });

          request
                .success(function(data) {
                  if(data.success) {
                    $scope.alerts.addAlert('success', "Registration Successful. Please check your inbox for confirmation email from Razorpay.", true);
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

  //Confirmation Controller
  .controller('ConfirmCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, $state, $stateParams, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.success = false;

      var token = $stateParams.token;

      if(!token) {
        $state.go('access.signin');
      }

      $scope.alerts.addAlert('info', 'Processing...', true);
  
      var request = $http({
                        method: "get",
                        url: "/user/confirm/"+token
                    });

      request
            .success(function(data) {
              $scope.alerts.resetAlerts();
              if(data.success) {
                $scope.success = true;  
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
  }])

  //Forgot Password Controller
  .controller('ForgotPasswordCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.data = {
        _token: CSRF_TOKEN
      }

      $scope.submit = function() {
          $scope.alerts.addAlert('info', 'Processing...', true);

          var request = $http({
                      method: "post",
                      url: "/user/password/reset",
                      transformRequest: transformRequestAsFormPost,
                      data: $scope.data
                  });

          request
                .success(function(data) {
                  if(data.success) {
                    $scope.alerts.addAlert('success', "Reset request sent. Please check your inbox for verification email from Razorpay.", true);
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
  
  //Reset Passsword Controller
  .controller('ResetPasswordCtrl', ['$scope', '$http', '$state', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', 'CSRF_TOKEN', 
    function($scope, $http, $state, $stateParams, alertsFactory, transformRequestAsFormPost, CSRF_TOKEN) {
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.success = false;

      $scope.data = {
        _token: CSRF_TOKEN
      }

      $scope.data.token = $stateParams.token;

      if(!$scope.data.token) {
        $state.go('access.signin');
      }
          
      $scope.submit = function($valid) {
          if(!$valid)  {
            $scope.alerts.addAlert('danger', 'Please fill all the fields correctly', true);     
            return true;     
          }

          $scope.alerts.addAlert('info', 'Processing...', true);

          var request = $http({
                      method: "post",
                      url: "/user/password/reset/"+$scope.data.token,
                      transformRequest: transformRequestAsFormPost,
                      data: $scope.data
                  });

          request
                .success(function(data) {
                  $scope.alerts.resetAlerts();
                  if(data.success) {
                    $scope.success = true;
                  }
                  else {
                    angular.forEach(data.errors, function(error, key) {
                      $scope.alerts.addAlert('danger', error);
                    });      
                  }
                })
                .error(function() {
                  $scope.alerts.addAlert('danger');
                })
      };
  }])

  //User profile Controller
  .controller('UserCtrl', ['$scope', '$http', '$state', 'user', 'CSRF_TOKEN', '$modal', 'alertsFactory', '$idle', '$keepalive',
    function($scope, $http, $state, user, CSRF_TOKEN, $modal, alertsFactory, $idle, $keepalive) {
      
      user.identity().then(function(data){
        $scope.user = data;
      });

      $scope.alerts = alertsFactory.getHandler();

      console.log($scope.alerts);

      $scope.activated = function(activated) {
        if(activated == 1) {
          return "Activated";
        }
        else {
          return "Not Activated";
        }
      };

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
            url: "/user/logout",
            data: $scope.data
        });

        request
          .finally(function() {
                user.identity(true);
          });

        return request;
      }
      function passwordChangeRequest(data) {
        $scope.alerts.addAlert('info', 'Processing...', true);

        data._token = CSRF_TOKEN;

        var request = $http({
          method: "post",
          url: "/password",
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
          $state.go('lockme', { "email": $scope.user.email}).finally(function(){
            closeModals();
          });
        });

      });
  }])

  //Application mode change controller
  .controller('modeCtrl', ['$scope', 'modeFactory',
    function($scope, modeFactory){
      $scope.modes = modeFactory.getModes;
      $scope.mode = modeFactory.getMode;
      $scope.selectMode = modeFactory.selectMode;
  }])

  //Dashboard Aggregations controller
  .controller('AggregationsCtrl', ['$scope', '$http', 'modeFactory',
    function($scope, $http, modeFactory) {

    //Aggreagates
    $scope.aggregations = {};

    $scope.aggregations.data = {
                        success: 0,
                        amount: 0,
                        txns: 0
                    };

    var request = $http.get("/"+modeFactory.getMode()+"/analytics/aggregations");

    request.success(function(result){
      
      if (result.data !== null) {
          if (parseInt(result.data.txn_count) !== 0)
              $scope.aggregations.data.success = parseInt(result.data.successful_txn_count * 100/result.data.txn_count);
          $scope.aggregations.data.amount = result.data.total_amount;
          $scope.aggregations.data.txns = result.data.txn_count;
      }
    });
  }])

  //Dashboard graphs/date picker controller
  .controller('DashboardCtrl', ['$scope', '$http', 'modeFactory', 'dateFactory',
    function($scope, $http, modeFactory, dateFactory) {

      
      initialiseStatType();
      initialiseGraphs();
      
      //Watches changes in parameters and trigger regeneration fo graph if any changes
      $scope.$watch('showSpline', function() {
         $scope.refreshGraph = !$scope.refreshGraph;
      });
    
      //@todo once angular 1.3 is stable switch to watchgroup
      $scope.$watch('statType + date.startDate + date.endDate',function() {
         generateGraphs();
      });

      function initialiseStatType(){
        //Stat Type handlers
        $scope.statType = 'day';

        $scope.stats = {day: "Daily", week: "Weekly", month: "Monthly", year: "Yearly"};

        $scope.setStat = function(type) {
          $scope.statType = type;
        };
      };

      function initialiseGraphs() {
        //Date Handlers
        $scope.date = dateFactory.getHandler($scope);
        
        //Graphs Initalisers
        $scope.showSpline = true;

        $scope.refreshGraph = false; //Variable that is toggled whenever we want the graph to be refreshed

        var graphData = {
          data: [ [0,0] ],
          options: {
            colors: [$scope.app.color.info, $scope.app.color.primary],
            series: { shadowSize: 3 },
            xaxis: {mode: 'time', timezone: "browser"},
            yaxis:{ font: { color: '#a1a7ac' }},
            grid: { hoverable: true, clickable: true, borderWidth: 0, color: '#dce5ec' },
            tooltip: true,
            tooltipOpts: {
              defaultTheme: false, 
              shifts: { x: 10, y: -25 }
            }
          }
        };

        $scope.successfull = graphData;
        $scope.transactions = graphData;
        
        $scope.successfull.options.tooltipOpts.content = 'Date: %x <br/> Count: %y';
        $scope.successfull.options.tooltipOpts.content = 'Date: %x <br/> Amount: %y';
      };

      function generateGraphs() {
        if(!$scope.date.startDate || !$scope.date.endDate) return;

        var from = parseInt(($scope.date.startDate.getTime())/1000) - 1;
        var to = parseInt(($scope.date.endDate.getTime())/1000) + 1;

        var request = $http.get("/" + modeFactory.getMode() + "/analytics/transactions?type="+$scope.statType+"&from=" + from + "&to="+ to);


        request.success(function(data){
          $scope.successfull.data = [];
          $scope.transactions.data = [];

          angular.forEach(data.data , function(value, key){
            $scope.successfull.data.push([
              parseInt(value.created_at)*1000,
              parseInt(value.count)
            ]);

            $scope.transactions.data.push([
              parseInt(value.created_at)*1000,
              parseInt(value.amount)
            ]);
          });

          $scope.successfull.options.xaxis.minTickSize = getMinTickSize($scope.statType);
          $scope.transactions.options.xaxis.minTickSize = getMinTickSize($scope.statType);

          $scope.refreshGraph = !$scope.refreshGraph;
        });
      };

      function getMinTickSize(statType){
        if(statType === 'week'){
          return [7, 'day'];
        }
        else{
          return [1, statType];
        }
      };
  }])

  //Transactions Listing Controller
  .controller('TransactionsListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
    function($scope, $http, modeFactory, alertsFactory, $state){
      //Intialise alerts and scope functions
      $scope.alerts = alertsFactory.getHandler();

      $scope.transactions = {
          data: {},
          id: '',
          count: 0,
          countStart: 0,
          countEnd: 0,
          skip: 0,
          from: 0,
          to: 4102444800,
          status: ''
      };


      $scope.$watch('transactions.status', regenerate);

      //@todo seperate refund controller later
      //Used for refunds list display route
      if($state.current.data.status) $scope.transactions.status = $state.current.data.status;

      generateTable();
      
      $scope.next= function() {
        clear('id');
        $scope.transactions.skip += 10;
        generateTable();
      }

      $scope.prev= function() {
        clear('id');
        $scope.transactions.skip -= 10;
        generateTable();
      }

      $scope.search= function() {
        clear('skip');
        generateTable();
      }

      $scope.regenerate = regenerate;

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

      function clear(field){
        if(field === 'id')
          $scope.transactions.id = '';
        if(field === 'skip')
          $scope.transactions.skip = 0;
      }

      function regenerate(){
        clear('skip');
        clear('id');
        generateTable();
      }

      function generateTable() {
        $scope.alerts.addAlert('info', "Processing... ", true);
        var query =
          "count=10" +
          "&skip="+ $scope.transactions.skip +
          "&from="+ $scope.transactions.from +
          "&to="+ $scope.transactions.to;

        if($scope.transactions.status !== '')
          query += "&status=" + $scope.transactions.status;

        if($scope.transactions.id === '')
          var request = $http.get("/" + modeFactory.getMode() +  "/transactions?" + query);
        else
          var request = $http.get("/" + modeFactory.getMode() +  "/transactions/" + $scope.transactions.id);
        
        request
        .success(function(data){
          $scope.alerts.resetAlerts();

          if(data.success) {
            $scope.transactions.data = data.data;

            $scope.transactions.count = data.count;

            $scope.transactions.countStart = $scope.transactions.skip + 1;

            if(data.count === 0) $scope.transactions.countEnd = $scope.transactions.countStart;

            else $scope.transactions.countEnd = $scope.transactions.countStart + $scope.transactions.count -1;

            $scope.allowPrev = $scope.transactions.countStart !== 1;
            
            $scope.allowNext=$scope.transactions.count >= 10;
          }
          else {
            angular.forEach(data.errors, function(value, key){
              $scope.alerts.addAlert('danger', value);
            });            
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