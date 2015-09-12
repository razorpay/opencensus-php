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

    $scope.getType = function(key, value) {
      var entity = key.substr(0, key.length - 3);

      var isTimestamp = function(key) {
        return key.substr(-3) === '_at';
      }

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

      else if(entity === 'payment') {
        return 'payment';
      }

      // We have a separate view for merchant entity
      else if(entity === 'merchant') {
        return 'merchant';
      }

      // All other entity links are considered here
      else if(isId(key)) {
        $scope.entity_type = entity;
        return 'id';
      }

      // Unknown type is entity specific things, like amount
      else {
        return 'unknown';
      }
    }
}]);
