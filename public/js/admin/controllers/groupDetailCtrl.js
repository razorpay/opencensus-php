"use strict";

app.controller('GroupDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, $stateParams, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.id = $stateParams.id;
    $scope.group = {
      id: $stateParams.id,
    };

    fetchGroup();

    function fetchGroup() {
      var request = $http.get('/admin/group/' + $scope.group.id);
      $scope.group.name = 'fdfasd';
      $scope.group.description = 'Some fine description';
      request.success(function (data) {
        console.log(data.data);
      }).error(function () {
        conole.log('Fetch Role Request Failed');
      });
    }
  }
]);
