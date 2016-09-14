"use strict";
//Add Funds Controller
app.controller('AddfundsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'user',
  'uiLoad',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, user, uiLoad, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.disableAddFunds = true;

    $scope.options = {
      'key': '',
      'amount': 50000,
      'name': '',
      'description': 'Add Funds to Account',
      'image': '',
      'handler': function (transaction) {
        $scope.transactionHandler(transaction);
      },
      'prefill': {
        'name': '',
        'email': '',
        'contact': ''
      },
      notes: { 'dashboard': 'true' },
      netbanking: true,

      amountInINR: 50000 / 100
    };
    $scope.addFunds = function () {
      try {
        $scope.options.amount = parseInt($scope.options.amountInINR * 100); // converting rupee to paise
        var rzp1 = new window.Razorpay($scope.options);
        rzp1.open();
      } catch (e) {
        $scope.alerts.addAlert('danger', 'An error occured - ' + e.message, true);
      }
    };
    $scope.transactionHandler = function (transaction) {
      transaction.amount = $scope.options.amount;
      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/addfunds',
        transformRequest: transformRequestAsFormPost,
        data: transaction
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Funds added successfully', true);
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
    fetchUser();
    fetchHost();
    fetchKey();
    function fetchUser() {
      user.identity().then(function (data) {
        $scope.options.name = data.name;
        $scope.options.prefill.email = data.email;
      });
    }

    function fetchHost() {
      $http.get('/apihost').success(function (data) {
        // data.data contains the API_URL environment variable
        loadCheckout(data.data);
      });
    }

    function loadCheckout(apiURL)
    {
      var api = document.createElement('a');
      api.href = apiURL;

      var checkoutURL = 'https://checkout.razorpay.com/';

      // We call this to ensure that Checkout is calling the correct API
      // Skipped in production
      if (typeof window.Razorpay !== 'function' && apiURL !== 'https://api.razorpay.com/v1/') {
        // This needs to be global
        window.Razorpay = {
          config: {
            api: api.protocol + '//' + api.hostname + '/',

            // path for checkout
            js: checkoutURL
          }
        };
      }

      checkoutURL += 'v1/checkout.js';

      uiLoad.loadScript(checkoutURL).then(function() {
        $scope.disableAddFunds = false;
      });
    }
    function fetchKey() {
      var request = $http.get('/' + $scope.mode + '/keys');
      request.success(function (data) {
        if (data.success) {
          if (data.data.count > 0) {
            $scope.options.key = data.data.items[0].id;
          } else {
            $scope.alerts.addAlert('danger', 'No valid api keys found, check Api Keys page.', true);
            $scope.disableAddFunds = true;
          }
        } else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);
