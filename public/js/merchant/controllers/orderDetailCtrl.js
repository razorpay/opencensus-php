//Single Payment Details controller
//Child of TransactionDetailCtrl
app.controller('OrderDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  '$modal',
  'alertsFactory',
  'transformRequestAsFormPost',
  function ($scope, $http, $stateParams, $modal, alertsFactory, transformRequestAsFormPost) {
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
