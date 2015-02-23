// Payment Listing Controller
// Child of TransactionListCtrl
app.controller('PaymentListCtrl', ['$scope', '$http', 'alertsFactory', '$state',
  function($scope, $http, alertsFactory, $state){

    $scope.getStatusClass = function(status) {
      var mapper = {
        created: "bg-light",
        authorized: "bg-info",
        captured: "bg-success",
        failed: "bg-danger"
      }
      return mapper[status];
    }

}]);