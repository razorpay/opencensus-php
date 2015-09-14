//Single Entity Details controller
app.controller('EntityDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory',
  function($scope, $http, $stateParams, alertsFactory) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.mode = $stateParams.mode;

    $scope.entity = {
      id: $stateParams.id
    };

    $scope.generate = function(entityType){
      $scope.entity.type = entityType;
      fetchEntity();
    }

    if($stateParams.type) {
      $scope.entity.type = $stateParams.type;
    }

    function fetchEntity() {
      var url = "/admin/" + $scope.mode +  "/fetchentity/" + $scope.entity.type + "/" + $scope.entity.id;
      var request = $http.get(url);

      request
      .success(function(data) {
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.entity = data.data;
        }
        else {
          angular.forEach(data.errors, function(error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      })
      .error(function(er) {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    // Type is same as returned by getType
    $scope.getState = function(type) {
      switch(type) {
        case 'merchant':
          return "app.merchants.detail({id: value})";
        case 'payment':
          return "app.payments({id:value, mode:mode})";
        case 'id':
          return "app.entitiesdetail({id:value, mode:mode, type: getEntity(key)})";
        default:
          // This needs to be a non-empty string
          return '-';
      }
    }

    $scope.displayValue = function(value, type) {
      // Set timezone to IST
      moment().zone(5.5);
      switch(type) {
        case 'timestamp':
          return moment(value*1000).format('D MMM YYYY h:mm:ss a (ddd) ') + 'IST';
        case 'amount':
          return 'INR ' + (value/100).toFixed(2);
        default:
          return value;
      }
    }

    // Removes _id from end
    $scope.getEntity = function(key) {
      return key.substr(0, key.length-3);
    }

    $scope.getType = function(key, value) {
      var entity = key.substr(0, key.length - 3);

      var isTimestamp = function(key) {
        return key.substr(-3) === '_at';
      }

      // These have their own views
      var specialEntities = ['merchant_id', 'payment_id'];

      var isId = function(key) {
        var validEntities = ["adjustment", "atom", "axis_genius",
          "axis_migs", "bank_account", "bank_account", "billdesk",
          "card", "dailysettlement", "hdfc", "iin", "kotak", "merchant",
          "mobikwik", "netbanking", "payment", "paytm", "refund",
          "settlement", "terminal", "transaction"
        ];

        // It needs to be suffixed with _id
        // and be a valid entity name for this to work

        return (key.substr(-3) === '_id')
          && (validEntities.indexOf(entity) > -1);
      }

      // Timestamps could be blank, which is why
      // we consider its value as well
      if(value && isTimestamp(key)) {
        return 'timestamp';
      }

      // All other entity links are considered here
      else if(isId(key)) {
        if (specialEntities.indexOf(key) > -1) {
          return $scope.getEntity(key);
        }
        else {
          return 'id';
        }
      }

      // Unknown type is entity specific things, like currency
      else {
        return 'unknown';
      }
    }
}]);
