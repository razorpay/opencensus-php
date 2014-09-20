//Single Payment Details controller
app.controller('PaymentDetailCtrl', ['$scope', '$http', '$stateParams', '$modal', 'modeFactory', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
  function($scope, $http, $stateParams, $modal, modeFactory, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.payment = {
      id: $stateParams.id
    };

    fetchPayment();

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
            return $scope.payment.amount - $scope.payment.amount_refunded;
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
            return $scope.payment.amount;
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
                    url: "/" + modeFactory.getMode() + "/payments/"  + $scope.payment.id + "/capture",
                    transformRequest: transformRequestAsFormPost,
                    data: data
                });

      request.success(function(data){
        if(data.success){
          $scope.alerts.addAlert('success', "Payment Captured", true);
          $scope.payment.status = "captured";
          $scope.payment.amount = captureAmount;
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

      var unrefundedAmount = parseInt($scope.payment.amount) - parseInt($scope.payment.amount_refunded);

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
                    url: "/" + modeFactory.getMode() + "/payments/"  + $scope.payment.id + "/refund",
                    transformRequest: transformRequestAsFormPost,
                    data: data
                });

      request.success(function(data){
        if(data.success){
          $scope.alerts.addAlert('success', "Payment Refunded", true);
      
          if(refundAmount == unrefundedAmount)
            $scope.payment.refund_status = 'full';
          else
            $scope.payment.refund_status = 'partial';

          $scope.payment.amount_refunded = parseInt($scope.payment.amount_refunded) + refundAmount; 
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

      var request = $http.get("/" + modeFactory.getMode() + "/payments/"  + $scope.payment.id + "/refunds");

      request.success(function(data){
        $scope.alerts.resetAlerts();
        
        if(data.success){
          $scope.payment.refunds = data.data;
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

    function fetchPayment() {  
      var request = $http.get("/" + modeFactory.getMode() +  "/payments/" + $scope.payment.id);

      request
      .success(function(data) {
        if(data.success) {
          $scope.payment = data.data.data[0];
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