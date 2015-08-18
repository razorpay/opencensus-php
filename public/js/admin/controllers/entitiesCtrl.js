//Entities Listing Controller
app.controller('EntitiesCtrl', ['$scope', '$http', 'alertsFactory', '$state', '$modal',
  function($scope, $http, alertsFactory, $state, $modal){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity_type = "payment";
    $scope.count = 10;
    $scope.filters = {};

    var gatewayList = [
      'all', 'atom', 'axis_genius', 'axis_migs', 'billdesk',
      'hdfc', 'kotak', 'paytm', 'netbanking_hdfc', 'sharp',
      'mobikwik'
    ];

    $scope.availableFilters = {
      payment: {
        status:       ['all', 'authorized', 'failed', 'captured', 'refunded'],
        verified:     ['all', 0, 1],
        method:       ['all', 'card', 'netbanking', 'wallet'],
        gateway:      gatewayList,
        email:        ['Contact Email']
      },
      merchant: {
        activated:    ['all', 0, 1],
        live:         ['all', 0, 1],
        hold_funds:   ['all', 0, 1]
      },
      terminal: {
        gateway:      gatewayList
      },
      transaction: {
        settlement_id: ['Settlement Id'],
        payment_id:    ['Payment Id'],
        settled:      ['all', 0, 1],
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

    $scope.mode = "live";
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

    $scope.$watch('mode + entity_type + count', function() {
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
      if(val===0)
        return 'no';
      else if(val===1)
        return 'yes';
      else
        return val;
    }

    $scope.next= function() {
      clear('id');
      $scope.entity.skip += 20;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.entity.skip -= 20;
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
.controller('entityDetailModalCtrl', ['$scope', '$modalInstance', 'data',
  function ($scope, $modalInstance, data) {
      $scope.data = data;

      $scope.notSorted = function(obj){
        if (!obj) {
            return [];
        }
        var data = Object.keys(obj);
        data.splice(-1,1)
        return data;
      }

      $scope.ok = function () {
        $modalInstance.close();
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
;
