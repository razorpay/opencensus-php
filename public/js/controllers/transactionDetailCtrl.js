//Single Transaction Details controller
app.controller('TransactionDetailCtrl', ['$scope', '$http', '$stateParams', '$modal', 'modeFactory', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
  function($scope, $http, $stateParams, $modal, modeFactory, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {
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
    
    $scope.openRefundModal  = function () {
      var modalInstance = $modal.open({
        templateUrl: 'refundModalContent.html',
        controller: 'RefundModalCtrl',
        resolve: {
          amount: function () {
            return $scope.transaction.amount - $scope.transaction.amount_refunded;
          }
        }
      });

      modalInstance.result.then(
        function (amount) {
          $scope.refund(amount);
        },
        function () {
          ;
        }
      );
    };

    $scope.openCaptureModal = function() {
      var modalInstance = $modal.open({
        templateUrl: 'captureModalContent.html',
        controller: 'CaptureModalCtrl',
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
        }
      );
    };

    $scope.capture = function(amount) {

      var captureAmount = parseInt(amount);

      if(!captureAmount){
        $scope.alerts.addAlert('danger', 'Invalid capture amount', true);
        return;
      }

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

    $scope.refund = function(amount) {

      var refundAmount = parseInt(amount);

      var unrefundedAmount = parseInt($scope.transaction.amount) - parseInt($scope.transaction.amount_refunded);

      if(!refundAmount || refundAmount > unrefundedAmount){
        $scope.alerts.addAlert('danger', 'Refund amount should be an integer and less than amount minus amount refunded.', true);
        return;
      }

      var data = {
        _token: CSRF_TOKEN,
        amount: refundAmount
      }

      var request = $http({
                    method: "post",
                    url: "/" + modeFactory.getMode() + "/transactions/"  + $scope.transaction.id + "/refund",
                    transformRequest: transformRequestAsFormPost,
                    data: data
                });

      request.success(function(data){
        if(data.success){
          $scope.alerts.addAlert('success', "Transaction Refunded", true);
      
          if(refundAmount == unrefundedAmount)
            $scope.transaction.refund_status = 'full';
          else
            $scope.transaction.refund_status = 'partial';

          $scope.transaction.amount_refunded = parseInt($scope.transaction.amount_refunded) + refundAmount; 
        }
        else {
          $scope.alerts.addAlert('danger', null, true);
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      })
    };

    $scope.showRefunds = function() {

      if($scope.isRefundsCollapsed === false) {
        $scope.isRefundsCollapsed = true;
        return;
      }

      var request = $http.get("/" + modeFactory.getMode() + "/transactions/"  + $scope.transaction.id + "/refunds");

      request.success(function(data){
        $scope.alerts.resetAlerts();
        
        if(data.success){
          $scope.transaction.refunds = data.data;
          $scope.isRefundsCollapsed = false;
        }
        else {
          angular.forEach(data.errors, function(error, key) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    function fetchTransaction() {  
      var request = $http.get("/" + modeFactory.getMode() +  "/transactions/" + $scope.transaction.id);

      request
      .success(function(data) {
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
.controller('CaptureModalCtrl', ['$scope', '$modalInstance', 'amount', 
  function($scope, $modalInstance, amount) {
    $scope.amount = amount;
    $scope.ok = function (amount) {
      $modalInstance.close(amount);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
}])
//Refund Modal Box Controller
.controller('RefundModalCtrl', ['$scope', '$modalInstance', 'amount', 
  function($scope, $modalInstance, amount) {
    $scope.amount = amount;
    $scope.ok = function (amount) {
      $modalInstance.close(amount);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
}]);