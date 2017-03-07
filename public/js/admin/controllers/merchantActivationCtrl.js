"use strict";

//Merchant Activation Detail Display Controller
app.controller('MerchantActivationCtrl', [
  '$scope',
  '$http',
  '$controller',
  '$stateParams',
  function ($scope, $http, $controller, $stateParams) {

    $controller('ActivationCtrl', {$scope: $scope});

    $scope.merchant = { id: $stateParams.id };

    $scope.files = {};
    $scope.companyInfo = null;
    $scope.panVerified = false;

    // This decides whether the admin context
    // options will be shown or not.
    $scope.admin = true;
    $scope.admin_force_edit = false;

    $scope.verificationToolTip = {
      true: 'Business Name matches Company Register',
      false: "Business Name doesn't match company register",
      'pending': "Click the verify button to fetch company data and verify"
    };

    /**
     * Either the edits must be forced, or the form must
     * be unlocked
     * @return bool
     */
    $scope.editable = function() {
      return ($scope.admin_force_edit || $scope.data.locked === false);
    };

    $scope.verifyBusinessName = function() {
      var company = null;
      try {
        company = $scope.companyInfo.company['Company Name'];
      }
      catch(TypeError) {
        return 'pending';
      }

      var canonicalize = function(str) {
        return str.replace(/\s/g, '').toUpperCase();
      };

      var bn1 = canonicalize($scope.data.business_name);
      var bn2 = canonicalize(company);

      return (bn1 === bn2);
    };

    $scope.getUrl = function(name, params) {
      switch(name) {
        case 'fetch_details':
          return '/admin/merchant/' + $scope.merchant.id + '/activation';

        case 'fetch_merchant_details':
          return '/admin/merchant/' + $scope.merchant.id + '/details';

        case 'upload_file':
          return '/activation/save/file';

        case 'submit_form':
          return '/activation';

        case 'save_step':
          return '/activation/save/step/' + params.step;
      }
    };

    var fetchMerchantDetails = function() {
      var request = $http.get($scope.getUrl('fetch_merchant_details'));
      request.success(function (data) {
        if (data.success) {
          $scope.merchant = data.data.merchant;
        } else {
          $scope.alerts.addAlert('danger', 'Merchant Info could not be fetched');
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', 'Merchant Info could not be fetched');
      });
    };

    fetchMerchantDetails();

    $scope.verifyPAN = function (signatories, pan_name, pan_number) {
      for (var i in signatories) {
        var person = signatories[i];
        if (person.PAN_DIN.toUpperCase() === pan_number.toUpperCase() && person.Name.toUpperCase() === pan_name.toUpperCase()) {
          $scope.panVerified = true;
        }
      }
    };

    $scope.getCompanyData = function(cin) {
      var request = $http.get('/admin/companies/' + cin + '/info');
      request.success(function (data) {
        if (data.success) {
          $scope.companyInfo = data.data;
          $scope.verifyPAN(data.data.signatories, $scope.data.promoter_pan_name, $scope.data.promoter_pan);
        } else {
          $scope.alerts.addAlert('danger', 'Company Info could not be fetched');
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', 'Company Info could not be fetched');
      });
    };
  }
]);
