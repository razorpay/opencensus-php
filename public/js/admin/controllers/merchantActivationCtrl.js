//Merchant Activation Detail Display Controller
app.controller('MerchantActivationCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  function($scope, $http, $stateParams, alertsFactory) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.merchant = { id: $stateParams.id };
    $scope.check = {};
    $scope.data = {
      1: {},
      2: {},
      3: {},
      4: {},
      5: {},
      6: {},
    };
    $scope.files = {};
    $scope.locked = true;
    $scope.companyInfo = null;

    $scope.panVerified = false;
    getData();

    $scope.verifyPAN = function(signatories, pan_name, pan_number) {
      for (var i in signatories) {
        var person = signatories[i];
        if (
          person.PAN_DIN.toUpperCase() === pan_number.toUpperCase() &&
          person.Name.toUpperCase() === pan_name.toUpperCase()
        ) {
          $scope.panVerified = true;
        }
      }
    };

    $scope.getCompanyData = function(cin) {
      var request = $http.get('/admin/companies/' + cin + '/info');
      request
        .success(function(data) {
          if (data.success) {
            $scope.companyInfo = data.data;
            $scope.verifyPAN(
              data.data.signatories,
              $scope.data['2'].promoter_pan_name,
              $scope.data['2'].promoter_pan
            );
          } else {
            $scope.alerts.addAlert(
              'danger',
              'Company Info could not be fetched'
            );
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', 'Company Info could not be fetched');
        });
    };

    String.prototype.humanize = function() {
      var word =  this.replace(/_/g,' ')
                      .split(' ')
                      .map(function(str,index) {
                        if(index === 0) {
                          return (str.charAt(0).toUpperCase() + str.slice(1));
                        }
                        return str;
                      })
                      .join(" ");

      return word;
    }

    function getData() {
      var request = $http.get(
        '/admin/merchant/' + $scope.merchant.id + '/details'
      );
      request
        .success(function(data) {
          if (data.success) {
            angular.forEach(data.data.merchant.steps_finished, function(
              value,
              key
            ) {
              $scope.check[value] = true;
            });
            angular.forEach(data.data.merchant.merchant_details, function(
              value,
              key
            ) {
              $scope.data[key] = value;
            });
            angular.forEach(data.data.activation.files, function(value, key) {
              $scope.files[key] = value;
            });
            $scope['onboarding'] = {};
            angular.forEach(data.data.onboarding, function(
              value,
              key
            ) {
              $scope['onboarding'][key.humanize()] = value;
            });
            $scope.merchant = data.data.merchant;
            $scope.locked = $scope.data['locked'];
          } else {
            $scope.alerts.addAlert('danger');
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger');
        });
    }
  },
]);
