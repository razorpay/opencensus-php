// Admin Actions Controller
app.controller('ActionsCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', '$modal',
  function($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.initiateSetl = function(channel){

      var request = $http({
                    method: "post",
                    url: "/admin/settlement/initiate/"+channel
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Settlement initiated successfully. Response: ' + JSON.stringify(data.data), true);
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

    $scope.addIIN = function(iin){
      var request = $http({
                    method: "post",
                    url: "/admin/iin/add",
                    transformRequest: transformRequestAsFormPost,
                    data: iin
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'IIN added successfully. Response: ' + JSON.stringify(data.data), true);
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

    $scope.verifyPayment = function(payment_id){
      var request = $http({
                    method: "get",
                    url: "/admin/payment/" + payment_id + "/verify"
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Payment Verified successfully', true);
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

    $scope.openInitiateSetl = function () {
      var modalInstance = $modal.open({
        templateUrl: 'initiateSetlModalContent.html',
        controller: 'initiateSetlModalCtrl'
      });

      modalInstance.result.then(
        function (channel) {
          $scope.initiateSetl(channel);
        },
        function () {
          ;
        });
    };

    $scope.openAddIIN = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addIINModalContent.html',
        controller: 'addIINModalCtrl',
      });

      modalInstance.result.then(
        function (iin) {
          $scope.addIIN(iin);
        },
        function () {
          ;
        });
    };

    $scope.openVerifyPayment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'verifyPaymentModalContent.html',
        controller: 'verifyPaymentModalCtrl',
      });

      modalInstance.result.then(
        function (id) {
          $scope.verifyPayment(id);
        },
        function () {
          ;
        });
    };

    $scope.openEditNewsletter = function () {

      var modalInstance = $modal.open({
        templateUrl: 'sendNewsletter.html',
        controller: 'sendNewsletterCtrl'
      });

      modalInstance.result.then(function(){
      });
    }

    $scope.downloadBeneficiaryFile = function () {

      $scope.date = moment().format('yyyy-MM-dd');
      var modalInstance = $modal.open({
        templateUrl: 'downloadBeneficiaryFile.html',
        controller: 'downloadBeneficiaryFileCtrl'
      });

      modalInstance.result.then(
        function (date) {
          if(date) {
            window.open('/admin/beneficiary/dl');
          }
          else {
            window.open('/admin/beneficiary/dl?date='+date);
          }
        },
        function () {
          ;
      });
    };

    $scope.generateBeneficiaryFile = function() {
      var request = $http({
        method: "post",
        url: "/admin/beneficiary",
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Beneficary file generated successfully', true);
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

}])
.controller('initiateSetlModalCtrl', ['$scope', '$modalInstance', '$http',
  function ($scope, $modalInstance, $http) {
      $scope.ok = function (channel) {
        $modalInstance.close(channel);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('addIINModalCtrl', ['$scope', '$modalInstance', '$http',
  function ($scope, $modalInstance, $http) {
      $scope.ok = function (iin) {
        $modalInstance.close(iin);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('verifyPaymentModalCtrl', ['$scope', '$modalInstance', '$http',
  function ($scope, $modalInstance, $http) {
      $scope.ok = function (id) {
        $modalInstance.close(id);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('sendNewsletterCtrl', ['$scope', '$modalInstance', '$http', 'admin',
  function ($scope, $modalInstance, $http, admin) {

      $scope.mailingLists = {
        all:      'All merchants',
        live:     'Live Merchants',
        recent:   'Recently signed up merchants',
        paytm:    'Paytm enabled merchants',
        mobiqwik: 'Mobiqwik enabled merchants'
      };

      $scope.lists = {
        all: true
      };

      admin.identity().then(function(admin) {
        $scope.adminEmail = admin.email;
      });

      $scope.test = function () {
        console.debug(arguments);
      };

      $scope.ok = function () {
        $modalInstance.close();
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('downloadBeneficiaryFileCtrl', ['$scope', '$modalInstance', '$http',
  function ($scope, $modalInstance, $http) {
      $scope.ok = function (id) {
        $modalInstance.close(id);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
