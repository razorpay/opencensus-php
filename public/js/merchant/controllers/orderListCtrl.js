// Payment Listing Controller
// Child of TransactionListCtrl
app.controller('OrderListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  function ($scope, $http, alertsFactory, $state) {
    $scope.getStatusClass = function (status) {
      var mapper = {
        created: 'bg-light',
        authorized: 'bg-info',
        captured: 'bg-success',
        failed: 'bg-danger',
        refunded: 'bg-primary',
        attempted: 'bg-info'
      };
      return mapper[status];
    };

  }
]);
