//Merchant List controller
app.controller('OrgsCtrl', [
  '$scope',
  '$http',
  function ($scope, $http) {
    $scope.merchants = {};
    $scope.count = 0;
  }
]);
