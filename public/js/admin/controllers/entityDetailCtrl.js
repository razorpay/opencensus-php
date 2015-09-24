//Single Entity Details controller
app.controller('EntityDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory', '$modal',
  function($scope, $http, $stateParams, alertsFactory, $modal) {
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

    // Terminal Specific actions
    $scope.terminal = {
      delete: function(id) {
        var request = $http.delete("/admin/" + $scope.mode +  "/terminal/" + id);
        request
        .success(function(data){
          if(data.success) {
            alert("Terminal deleted");
          }
          else {
            alert(data.errors);
          }
        })
        .error(function(){
          alert("There was an error while deleting the terminal");
        });
      },
      edit: function(id, data) {
        delete data['id'];
        // Lets remove all the empty variables
        for(var i in data) {
          if(data[i] === '' || data[i] === null) {
            delete data[i];
          }
        }
        var request = $http.put("/admin/" + $scope.mode +  "/terminal/" + id, data);
        request
        .success(function(data){
          if(data.success) {
            alert("Terminal edit successfully");
            window.location.reload();
          }
          else {
            alert(data.errors);
          }
        })
        .error(function(){
          alert("There was an error while deleting the terminal");
        });
      }
    }

    $scope.toJson = function(data) {
      return angular.toJson(data, 4);
    };

    $scope.open = {
      'terminalEdit': function(terminal) {
        var modalInstance = $modal.open({
          templateUrl: 'editTerminal.html',
          controller: 'editTerminalModalCtrl',
          resolve: {
            current: function() {
              return terminal;
            }
          }
        });

        modalInstance.result.then(
          function (input) {
            delete input['merchant_id'];
            $scope.terminal.edit(input.id, input);
          },
          function () {
            ;
          });
      }
    }

    $scope.getKeys = function() {
      var keys = Object.keys($scope.entity);
      return keys;
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
}])
.controller('editTerminalModalCtrl', ['$scope', '$modalInstance', '$http', 'current',
  function ($scope, $modalInstance, $http, current) {
    // This is the current terminal current
    $scope.terminal = {
      gateway_access_code: current.gateway_access_code,
      gateway_merchant_id: current.gateway_merchant_id,
      gateway_terminal_id: current.gateway_terminal_id,
      id: current.id,
      card: current.card,
      gateway: current.gateway
    };

    console.debug($scope.terminal);

    $scope.ok = function (terminal) {
      $modalInstance.close(terminal);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
}]);
