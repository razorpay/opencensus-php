//Admin List controller
app.controller('AdminsCtrl', ['$scope', '$http', '$modal', 'admin', 'alertsFactory', 'CSRF_TOKEN', 'transformRequestAsFormPost',
  function($scope, $http, $modal, admin, alertsFactory, CSRF_TOKEN, transformRequestAsFormPost) {

  $scope.admins = {};

  $scope.alerts = alertsFactory.getHandler();

  admin.identity().then(function(data){
    $scope.admin = data;
  });

  generateTable();
  
  $scope.delete = function(id) {
    $scope.alerts.addAlert('info', 'Processing...', true);

    var request = $http.get("/admin/users/" + id + "/delete?_token="+CSRF_TOKEN);

    request
    .success(function(data){
      if(data.success) {
        $scope.alerts.addAlert('success', "Admin deleted successfully", true);

        generateTable();
      }
      else {
        $scope.alerts.resetAlerts();
        angular.foreach(data.errors, function(value, key){
          $scope.alerts.addAlert('danger', value);
        });
      }
    })
    .error(function(){
      $scope.alerts.addAlert('danger', null, true);
    });
  };

  $scope.createAdmin = function () {
    var modalInstance = $modal.open({
      templateUrl: 'newAdminModalContent.html',
      controller: 'newAdminModalCtrl',
      size: 'lg'
    });

    modalInstance.result.then(
      function (data) {
        console.log(data);
        newAdminRequest(data);
      },
      function () {
        ;
      });
  };

  function newAdminRequest(data){
    $scope.alerts.addAlert('info', 'Processing...', true);

    data._token = CSRF_TOKEN;

    var request = $http({
                  method: "post",
                  url: "/admin/users/add",
                  transformRequest: transformRequestAsFormPost,
                  data: data
    });

    request
    .success(function(data){
      if(data.success) {
        $scope.alerts.addAlert('success', 'Admin created successfully', true);
        generateTable();
      }
      else {
        $scope.alerts.resetAlerts();
        angular.forEach(data.errors, function(value, key){
          $scope.alerts.addAlert('danger', value);
        });
      }
    })
    .error(function(){
      $scope.alerts.addAlert('danger', null, true);
    });
  };

  function generateTable() {
    var request = $http.get("/admin/users");

    request
    .success(function(data){
      if(data.success) {

        $scope.admins = data.data;
      }
    });
  }
}])
.controller('newAdminModalCtrl', ['$scope', '$modalInstance', 
  function ($scope, $modalInstance) {
    $scope.ok = function (data) {
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
}]);