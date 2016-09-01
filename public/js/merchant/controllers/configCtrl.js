"use strict";
/**
 * Webhooks Ctrl
 */
app.controller('ConfigCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  '$upload',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, $upload) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.config = {};

    $scope.showColorPicker = false;
    $scope.showImageSelector = false;

    $scope.onFileSelect = saveFile;

    $scope.setConfig = function(config) {
      $scope.config.brand_color = config.brand_color ? config.brand_color : null;

      // This always stays as a string, except when we send it back
      $scope.config.transaction_report_email = config.transaction_report_email.join(',');

      /**
       * API is currently returning invalid logo urls
       * so we need to translate it into a valid URL
       */
      if ((config.logo_url !== null) && !/^http/.test(config.logo_url)) {
        $scope.config.logo_url = 'https://cdn.razorpay.com' + config.logo_url.replace(/\.([^\.]+$)/,'_medium.$1');
      }
      else {
        $scope.config.logo_url = config.logo_url;
      }

    };

    $scope.fetchConfig = function() {

      var request = $http({
        method: 'get',
        url: '/config'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.setConfig(data.data);
          if ($scope.config.brand_color !== null) {
            $scope.showColorPicker = true;
          }

          if ($scope.config.logo_url === null) {
            $scope.showImageSelector = true;
          }
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    // Fetch the config on load
    $scope.fetchConfig();

    $scope.save = function(config) {
      var data = {
        brand_color: config.brand_color ? config.brand_color.substr(1).toUpperCase() : null,
        transaction_report_email: config.transaction_report_email ? config.transaction_report_email.split(',') : null
      };

      var request = $http({
        "method": 'PUT',
        "url": '/config',
        "data": data,
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('success', 'Configuration Updated', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    function saveFile($files, fieldname) {
      var file = $files[0];
      var allowed_types = [
        'image/jpeg',
        'image/png',
        'image/jpg'
      ];
      if (allowed_types.indexOf(file.type) <= -1) {
        $scope.alerts.addAlert('danger', 'Invalid filetype. Only jpg/jpeg and png files are allowed.', true);
        return;
      }
      if (file.size > 1048576) {
        $scope.alerts.addAlert('danger', 'Max file size allowed is 1 MB.', true);
        return;
      }
      $scope.alerts.addAlert('info', 'Uploading...', true);
      var request = $upload.upload({
        url: '/config/logo',
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
          $scope.alerts.addAlert('success', 'File Uploaded Successfully', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', 'File upload failed.', true);
      }).finally(function () {
        $scope.locked = false;
      });
    }
  }
]);
