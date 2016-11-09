app.controller('OrgsCtrl', ['$scope', '$http', 'permissionsFactory', function ($scope, $http, permissionsFactory) {
  var request = $http.get('/admin/' + $scope.orgId + '/permissions');
  request.success(function (data) {
    permissionsFactory.setPermissions(data.data)
  }).error(function () {
    console.log('PERMISSIONS_GET_REQ_FAILED');
    //TODO Remove this later, setting dummy permissions
    permissionsFactory.setPermissions([{
      id: 'abcde1234',
      name: 'test_permissions',
      description: 'permission_description'
    }])
  });
}]);
