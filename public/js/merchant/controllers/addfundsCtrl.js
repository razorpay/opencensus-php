//Add Funds Controller
app.controller('AddfundsCtrl', ['$scope', '$http', 'alertsFactory','user', 'modeFactory', 'uiLoad', 'transformRequestAsFormPost',
  function($scope, $http, alertsFactory, user, modeFactory, uiLoad, transformRequestAsFormPost){
    $scope.mode = modeFactory.getMode();

    $scope.alerts = alertsFactory.getHandler();

    $scope.options = {
        'key': '',
        'amount': '50000',
        'name': '',
        'description': 'Add Funds to Account',
        'image': '',
        'handler': function(transaction){
          $scope.transactionHandler(transaction);
        },
        'prefill': {
            'name': '',
            'email': '',
            'contact': ''
        },
        notes: {
            'dashboard': 'true'
        },
        netbanking: true
    }
    
    $scope.addFunds = function() {
      try{
        var rzp1 = new Razorpay($scope.options);
        rzp1.open();
      }
      catch(e)
      {
        $scope.alerts.addAlert("danger", "An error occured - " + e.message, true);
      }
    }

    $scope.transactionHandler = function(transaction)
    {
      transaction.amount = $scope.options.amount;

      var request = $http({
                    method: "post",
                    url: "/" + modeFactory.getMode() + "/addfunds",
                    transformRequest: transformRequestAsFormPost,
                    data: transaction
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', "Funds added successfully", true);
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
    
    fetchUser();
    fetchHost();
    fetchKey();
   
    function fetchUser() {
      user.identity().then(function(data){
        $scope.options.name = data.name;
        $scope.options.prefill.email = data.email;
      });
    }

    function fetchHost() {
      if(window.location.hostname == "betadashboard.razorpay.com") {
        uiLoad.loadScript("https://betacheckout.razorpay.com/v1/checkout.js");
      }
      else {
        uiLoad.loadScript("https://checkout.razorpay.com/v1/checkout.js");
      }

      $http.get('/apihost').success(function(data){
        var host = data.data.split( '/' );
        $scope.options.protocol = host[0].substring(0, host[0].length - 1);
        $scope.options.hostname = host[2];
      });    
    }

    function fetchKey(){
      var request = $http.get('/'+$scope.mode+'/keys');

      request
      .success(function(data){     
        if(data.success) {
          if(data.data.count > 0) {
            $scope.options.key = data.data.items[0].id;
            $scope.disableAddFunds = false;
          }
          else {
            $scope.alerts.addAlert('danger', "No valid api keys found, check Api Keys page.", true);
            $scope.disableAddFunds = true;
          }
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      })
    }
}]);