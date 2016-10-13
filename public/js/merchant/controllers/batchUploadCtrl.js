"use strict";
//Add Funds Controller
app.controller('BatchUploadCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'user',
  'uiLoad',
  'transformRequestAsFormPost',
  '$upload',
  '$state',
  function ($scope, $http, alertsFactory, user, uiLoad, transformRequestAsFormPost, $upload, $state) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.batchForm = {
      batchFile: null,
      fileFieldName: '',
      type: 'refund'
    };

    $scope.onFileSelect = function ($files, fieldname) {
      var file = $files[0];

      $scope.batchForm.batchFile = file;
      $scope.batchForm.fileFieldName = fieldname;
    };

    $scope.batchUpload = function () {

      var request = $upload.upload({
        url: '/' + $scope.mode + '/batches',
        method: 'POST',
        file: $scope.batchForm.batchFile,
        fileFormDataName: $scope.batchForm.fileFieldName,
        data: { type: $scope.batchForm.type }
      });

      request.success(function (data, status, headers, config) {
        if (data.success) {
          $state.go('app.batch.list');
        }
      }).error(function () {

      });

    };
  }
]);
