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
        attempted: 'bg-info',
        paid: 'bg-success'
      };
      return mapper[status];
    };

  }
]);
