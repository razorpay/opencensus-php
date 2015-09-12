//Entities Listing Controller
app.controller('EntitiesCtrl', ['$scope', '$http', 'alertsFactory', '$state', '$modal', '$stateParams',
  function($scope, $http, alertsFactory, $state, $modal, $stateParams){

    $scope.entity_type = $stateParams.type;
    $scope.mode = $stateParams.mode;

    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.count = 10;
    $scope.filters = {};

    $scope.headings =[];
    $scope.refreshTable = true;

    $scope.entity = {
        items: {},
        id: '',
        count: 0,
        countStart: 0,
        countEnd: 0,
        skip: 0
    };

    var gatewayList = [
      'all', 'atom', 'axis_genius', 'axis_migs', 'billdesk',
      'hdfc', 'kotak', 'paytm', 'mobikwik', 'netbanking_hdfc', 'sharp'
    ];

    // This is the list of available filters
    // len==1 means a text input, rest are drop-downs
    // This list is alphabetically sorted, take care to maintain that
    $scope.availableFilters = {
      adjustment: {
        merchant_id: ['Merchant Id']
      },
      axis_genius: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      axis_migs: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      billdesk: {
        AuthStatus: ['all', '0001', '0300', '0002', '0399', 'NA'],
        received:   ['all', 0, 1],
        payment_id: ['Payment Id']
      },
      card: {
        iin:          ['IIN'],
        merchant_id:  ['Merchant Id'],
        network:      ['all', 'visa', 'mastercard', 'maestro', 'dinersclub', 'amex', 'rupay']
      },
      hdfc: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      merchant: {
        activated:    ['all', 0, 1],
        hold_funds:   ['all', 0, 1],
        live:         ['all', 0, 1]
      },
      netbanking: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      payment: {
        bank:         ['Bank Code'],
        email:        ['Contact Email'],
        gateway:      gatewayList,
        merchant_id:  ['Merchant Id'],
        method:       ['all', 'card', 'netbanking', 'wallet'],
        refund_status: ['all', 'partial', 'full'],
        status:       ['all', 'authorized', 'failed', 'captured', 'refunded'],
        verified:     ['all', 0, 1],
        wallet:       ['all', 'paytm', 'mobikwik']
      },
      paytm: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      mobikwik: {
        payment_id: ['Payment Id'],
        received:   ['all', 0, 1]
      },
      refund: {
        merchant_id: ['Merchant Id']
      },
      terminal: {
        gateway:      gatewayList
      },
      transaction: {
        entity_id:    ['Payment/Refund/Settlement Id'],
        merchant_id:  ['Merchant Id'],
        settled:      ['all', 0, 1],
        settlement_id: ['Settlement Id'],
        type:         ['all', 'payment', 'refund', 'settlement', 'adjustment']
      }
    };


    // This loop initializes the filters object
    for(var entity in $scope.availableFilters) {
      $scope['filters'][entity] = {};
      var filters = $scope.availableFilters[entity];
      //console.debug(filters);
      for(var filter in filters) {
        var def

        // dropdown
        if(filters[filter].length > 1) {
          // The first value is the default
          $scope['filters'][entity][filter] = filters[filter][0];

          // Only call watch if the property is a dropdown
          $scope.$watch('filters.' + entity + '.' + filter, function() {
            $scope.showTable();
          });
        }
      }
    }

    $scope.$watch('mode + entity_type + count', function(x) {
      $stateParams.mode = $scope.mode;
      $stateParams.entity_type = $scope.entity_type;

      $scope.showTable();
    });

    $scope.notSorted = function(obj){
        if (!obj) {
            return [];
        }
        var data = Object.keys(obj);
        data.splice(-1,1)
        return data;
    }

    $scope.displayFilter = function(val)
    {
      var labels = {
        0: 'no',
        1: 'yes',
        '0001': 'BillDesk Cancel',
        '0300': 'Success',
        '0002': 'Bank Pending',
        '0399': 'Bank Cancel Auth Error',
        'NA': 'Invalid Input'
      }
      if(val in labels) {
        return labels[val];
      }
      else {
        return val;
      }
    }

    $scope.next= function() {
      clear('id');
      $scope.entity.skip += $scope.count;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.entity.skip -= $scope.count;
      generateTable();
    }

    $scope.search= function() {
      clear('skip');

      var request = $http.get("/admin/" + $scope.mode +  "/fetchentity/" + $scope.entity_type + "/" + $scope.entity.id);

      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.showDetail(data.data);
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

    if($stateParams.type && $stateParams.id) {
      $scope.entity.id = $stateParams.id;
      $scope.entity_type = $stateParams.type;
      $scope.search();
    }

    function clear(field){
      if(field == 'id')
        $scope.entity.id = '';
      else
        $scope.entity.skip = 0;
    }

    $scope.showDetail = function(data) {
      var modalInstance = $modal.open({
        templateUrl: 'entityDetailModalContent.html',
        controller: 'entityDetailModalCtrl',
        size: 'lg',
        resolve: {
          data: function() {
            return data;
          },
          mode: function() {
            return $scope.mode;
          }
        }
      });
    }

    $scope.showTable = function() {
      clear('id');
      clear('skip');
      generateTable();
    }

    function generateTable() {

      if(!$scope.entity_type){
        console.log("Error: No Entity Type Specified");
        return;
      }

      var query = "count="+$scope.count+"&skip="+ $scope.entity.skip;
      for(var filterName in $scope.filters[$scope.entity_type]) {
        var value = $scope.filters[$scope.entity_type][filterName];
        if(value !== 'all' && value!== ''){
          query+= ('&' + filterName + '=' + encodeURIComponent(value))
        }
      }

      var request = $http.get("/admin/" + $scope.mode +  "/fetchentity/" + $scope.entity_type + "?" + query);

      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.headings = data.data.headings;
          $scope.entity.items = data.data.items;
          $scope.entity.count = parseInt(data.data.count);

          $scope.entity.countStart = $scope.entity.skip + 1;

          if(data.data.count == 0)
            $scope.entity.countEnd = $scope.entity.countStart;
          else
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count -1;

          $scope.allowPrev = $scope.entity.countStart != 1;

          $scope.allowNext = $scope.entity.count >= 10;
        }
        else {
          if(data.errors) {
            angular.forEach(data.errors, function(value, key){
              $scope.alerts.addAlert('danger', value);
            });
          }
          else {
            $scope.alerts.addAlert('danger', null, true);
          }
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    }
}])
.controller('entityDetailModalCtrl', ['$scope', '$modalInstance', '$http', 'data', 'mode',
  function ($scope, $modalInstance, $http, data, mode) {
      $scope.data = data;
      $scope.mode = mode;

      // Removes the last key from the obj
      $scope.notSorted = function(obj){
        if (!obj) {
            return [];
        }
        return Object.keys(obj).slice(0, -1);
      }

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
        }
      }

      /*$scope.updateEntity = function(key, value) {
        // Remove `_id` from the end
        $scope.entity_type = key.substr(0, key.length -3);

        var request = $http.get("/admin/" + $scope.mode +  "/fetchentity/" + $scope.entity_type + "/" + value);

        request
        .success(function(data){

          if(data.success) {
            $scope.data = data.data;
          }
        })
        .error(function(){
          //$scope.alerts.addAlert('danger', null, true);
        });
      }*/

      $scope.displayValue = function(value, type) {
        // Set timezone to IST
        moment().zone(5.5);
        switch(type) {
          case 'timestamp':
            return moment(value*1000).format('D MMM YYYY h:mm:ss a (ddd) ') + 'IST';
          case 'amount':
            return 'INR ' + (value/100).toFixed(2);
        }
      }

      $scope.ok = function () {
        $modalInstance.close();
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
;
