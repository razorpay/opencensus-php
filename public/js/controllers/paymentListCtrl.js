//Payment Listing Controller
//Child of TransactionListCtrl
app.controller('PaymentListCtrl', ['$scope', '$http', 'modeFactory', 'alertsFactory', '$state',
  function($scope, $http, modeFactory, alertsFactory, $state){

    $scope.getStatusClass = function(status) {
      var mapper = {
        open: "bg-light",
        authorized: "bg-info",
        captured: "bg-success",
        failed: "bg-danger"
      }
      return mapper[status];
    }

}]);