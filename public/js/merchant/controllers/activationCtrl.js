"use strict";

//Activation Form Controller
app.controller('ActivationCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$upload',
  'user',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $upload, user, organization) {
    $scope.steps = {
      percent: 0,
      step1: true
    };
    $scope.check = {};
    $scope.data = {
      1: {},
      2: {},
      3: {},
      4: {},
      5: {},
      6: {}
    };

    $scope.formAlerts = alertsFactory.getHandler();
    $scope.alerts = {
      1: alertsFactory.getHandler(),
      2: alertsFactory.getHandler(),
      3: alertsFactory.getHandler(),
      4: alertsFactory.getHandler(),
      5: alertsFactory.getHandler(),
      6: alertsFactory.getHandler()
    };
    $scope.fileAlerts = {
      business_proof: alertsFactory.getHandler(),
      business_operation_proof: alertsFactory.getHandler(),
      business_pan_proof: alertsFactory.getHandler(),
      address_proof: alertsFactory.getHandler(),
      promoter_proof: alertsFactory.getHandler(),
      promoter_pan_proof: alertsFactory.getHandler(),
      promoter_address_proof: alertsFactory.getHandler()
    };
    $scope.submit = function (step) {
      var finalStep = 6;
      if ($scope.accountDetails) {
        finalStep = 4;
      }

      if (step !== finalStep) {
        saveStep(step);
      } else {
        submitForm(step);
      }
    };
    $scope.onFileSelect = saveFile;
    var addressCopyToggle = false;
    $scope.genOperation = function (flag) {
      addressCopyToggle = flag;
      if (flag) {
        $scope.data.business_operation_address = $scope.data.business_registered_address;
        $scope.data.business_operation_state = $scope.data.business_registered_state;
        $scope.data.business_operation_city = $scope.data.business_registered_city;
        $scope.data.business_operation_pin = $scope.data.business_registered_pin;
      }
    };
    $scope.changeOperationalAddress = function (input) {
      if (addressCopyToggle && input.substr(0,19) === 'business_registered') {
        $scope.data[input.replace('registered', 'operation')] = $scope.data[input];
      }
    };
    getData();
    function getData() {
      var url = '/activation/details';
      if ($scope.accountDetails) {
        url += '/' + $scope.account;
      }

      var request = $http.get(url);

      request.success(function (data) {
        var steps_finished = data.data.steps_finished;
        angular.forEach(steps_finished, function (value) {
          $scope.check[value] = true;
        });
        angular.forEach(data.data, function (value, key) {
          $scope.data[key] = value;
        });
        $scope.data.bank_account_number_confirmation = $scope.data.bank_account_number;

        angular.forEach(data.data.files, function (key) {
          $scope.fileAlerts[key].addAlert('success', 'File already uploaded');
        });
        if (data.data.submitted === 1) {
          user.identity().then(function (data) {
            $scope.data.activated = data.activated;
            if (data.activated == 1) {
              $scope.formAlerts.addAlert('info', 'Your account is already activated');
            } else
              $scope.formAlerts.addAlert('info', 'Form has been submitted for activation and is pending admin response');
          });
        }
        if (data.data.locked === 1) {
          $scope.locked = true;
        }

        // ====
        // Heimdall specific
        // ====

        if ($scope.org === 'hdfc') {
          if (!$scope.data.bank_branch_ifsc) {
            $scope.data.bank_branch_ifsc = 'HDFC';
          }

          if (!$scope.data.bank_account_type) {
            $scope.data.bank_account_type = 'Current';
          }
        }
      });
    }
    function saveStep(step) {
      var data = $scope.data;

      var bankStep = 4;
      if ($scope.accountDetails) {
        bankStep = 2;
      }

      if (step === bankStep) {
        if (data.bank_account_number !== data.bank_account_number_confirmation) {
          $scope.alerts[step].addAlert('danger', 'Bank Account Number doesn\'t match');
          return;
        }
        data = angular.copy(data, {});
        delete data.bank_account_number_confirmation;
      }

      var url = '/activation/save/step/' + step;
      if ($scope.accountDetails) {
        url += '/' + $scope.account;
      }

      var request = $http({
        method: 'post',
        url: url,
        transformRequest: transformRequestAsFormPost,
        data: data
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts[step].addAlert('success', 'Step Saved Successfully', true);
          $scope.check[step] = true;
          $scope.refreshUser(true);
        } else {
          $scope.alerts[step].resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts[step].addAlert('danger', value);
          });
          $scope.check[step] = false;
        }
      }).error(function () {
        $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
        $scope.check[step] = false;
      });
    }
    function saveFile($files, fieldname) {
      var file = $files[0];
      var allowed_types = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/x-pdf'
      ];
      if (allowed_types.indexOf(file.type) <= -1) {
        $scope.fileAlerts[fieldname].addAlert('danger', 'Invalid filetype. Only jpg, png, pdf files are allowed.', true);
        return;
      }
      if (file.size > 2000000) {
        $scope.fileAlerts[fieldname].addAlert('danger', 'Max file size 2 MB. Convert the file to an image before uploading if necessary.', true);
        return;
      }
      $scope.locked = true;
      $scope.fileAlerts[fieldname].addAlert('info', 'Uploading...', true);

      var url = '/activation/save/file' ;
      if ($scope.accountDetails) {
        url += '/' + $scope.account;
      }

      var request = $upload.upload({
        url: url,
        method: 'POST',
        file: file,
        fileFormDataName: fieldname,
        formDataAppender: function (fd, key, val) {
          if (angular.isArray(val)) {
            angular.forEach(val, function (v) {
              fd.append(key, v);
            });
          } else {
            fd.append(key, val);
          }
        }
      });
      request.success(function (data) {
        if (data.success) {
          $scope.fileAlerts[fieldname].addAlert('success', 'File Uploaded Successfully', true);
        } else {
          $scope.fileAlerts[fieldname].resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.fileAlerts[fieldname].addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.fileAlerts[fieldname].addAlert('danger', 'File upload failed.', true);
      }).finally(function () {
        $scope.locked = false;
      });
    }
    function checkInputDateSupport() {
      var input = document.createElement('input');
      input.setAttribute('type', 'date');
      var notADateValue = 'not-a-date';
      input.setAttribute('value', notADateValue);
      return (input.value !== notADateValue);
    }
    function submitForm(step) {
      if ($scope.data.agree_terms !== true) {
        $scope.alerts[step].addAlert('danger', 'You must agree to the terms & conditions to use Razorpay services', true);
        return;
      }

      var url = '/activation' ;
      if ($scope.accountDetails) {
        url += '/' + $scope.account;
      }

      var request = $http({
        method: 'post',
        url: url,
        transformRequest: transformRequestAsFormPost
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts[step].addAlert('success', 'Form submitted Successfully!', true);
          $scope.check[step] = true;
          getData();
        } else {
          $scope.alerts[step].resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts[step].addAlert('danger', value);
          });
          $scope.check[step] = false;
        }
      }).error(function () {
        $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
        $scope.check[step] = false;
      });
    }

    // =====
    // Heidmall specific changes
    // =====

    // Get org details for certain display things
    organization.fetchCurrentOrg().then(function (data) {
      $scope.org = data.custom_code;
    });
  }
])

.controller('AccountActivationCtrl', [
  '$scope',
  function ($scope) {
    $scope.accountDetails = true;
  }
]);
