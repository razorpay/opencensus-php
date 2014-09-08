//Single Transaction Details controller
app.controller('TransactionDetailCtrl', ['$scope', '$http', '$stateParams', 'modeFactory', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
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
          $scope.transaction = data.data.data[0];
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

    $scope.open = function () {
      var modalInstance = $modal.open({
        templateUrl: 'captureModalContent.html',
        controller: ModalInstanceCtrl,
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
}]);