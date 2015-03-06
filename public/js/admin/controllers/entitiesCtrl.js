//Entities Listing Controller
app.controller('EntitiesCtrl', ['$scope', '$http', 'alertsFactory', '$state', '$modal',
  function($scope, $http, alertsFactory, $state, $modal){
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.entity_type = "transaction";
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

    generateTable();

    $scope.$watch('mode + entity_type', function() {
      clear('skip');
      clear('id');
      generateTable();
    });

    $scope.notSorted = function(obj){
        if (!obj) {
            return [];
        }
        var data = Object.keys(obj);
        data.splice(-1,1)
        return data;
    }

    $scope.next= function() {
      clear('id');
      $scope.entity.skip += 10;
      generateTable();
    }

    $scope.prev= function() {
      clear('id');
      $scope.entity.skip -= 10;
      generateTable();
    }

    $scope.search= function() {
      clear('skip');
      
      var request = $http.get("/admin/" + $scope.mode +  "/" + $scope.entity_type + "/" + $scope.entity.id);

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
      $scope.entity[field] = '';
    }

    $scope.showDetail = function(data) {
      var modalInstance = $modal.open({
        templateUrl: 'entityDetailModalContent.html',
        controller: 'entityDetailModalCtrl',
        resolve: {
          data: function() {
            return data;
          }
        }
      });
    }

    function generateTable() {

      if(!$scope.entity_type){
        console.log("Error: No Entity Type Sepcified");
        return;
      }

      var query =
        "count=10" +
        "&skip="+ $scope.entity.skip;

        var request = $http.get("/admin/" + $scope.mode +  "/" + $scope.entity_type + "?" + query);

      request
      .success(function(data){
        $scope.alerts.resetAlerts();

        if(data.success) {
          $scope.headings = data.data.headings;
          $scope.entity.items = data.data.items;
          $scope.entity.count = data.data.count;
          
          $scope.entity.countStart = $scope.entity.skip + 1;
          
          if(data.data.count == 0)
            $scope.entity.countEnd = $scope.entity.countStart;
          else
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count -1;

          $scope.allowPrev = $scope.entity.countStart != 1;

          $scope.allowNext = $scope.entity.count >= 10;
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