"use strict";

app.controller('FeaturesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$modal',
  function ($scope, $http, alertsFactory, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.fetched = false;
    $scope.fcEnabled; // true for only M1
    $scope.fcOpted = false; // true for M1 and M3

    $scope.fcToggle = function (evt) {
      $scope.fcEnabled = !$scope.fcEnabled;
    }

    function parseAndSetFeatures (features) {
      var flashCheckout = features.filter(function (feature) {
        return feature.feature === 'flashcheckout';
      });
      if (flashCheckout.length > 0) {
        flashCheckout = flashCheckout.shift().value;
      }
      var noFlashCheckout = features.filter(function (feature) {
        return feature.feature === 'noflashcheckout';
      });
      if (noFlashCheckout.length > 0) {
        noFlashCheckout = noFlashCheckout.shift().value;
      }
      $scope.fcOpted = (flashCheckout || noFlashCheckout);
      $scope.fcEnabled = flashCheckout;
    }

    function fetchFeatures() {
      var request = $http({
        method: 'get',
        url: '/features'
      });
      request.success(function (data) {
        if (data.success) {
          $scope.fetched = true;
          var features = data.data.features;
          parseAndSetFeatures(features);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    $scope.updateFeatures = function (optOutReason) {
      var flashCheckout = $scope.fcEnabled ? 1 : 0;
      var noFlashCheckout = !$scope.fcEnabled ? 1 : 0;

      var featureData = {
        features: {
          flashcheckout: flashCheckout,
          noflashcheckout: noFlashCheckout,
        }
      };

      if (optOutReason) {
        featureData.optout_reason = optOutReason;
      }
      return updateFeatures(featureData);
    };

    $scope.optOutFc = function () {
      // case when merchant is opting out for first time
      var modalInstance = $modal.open({
        templateUrl: 'optOutReasonModal.html',
        controller: 'optOutReasonModalCtrl',
        resolve: {
          optOutReason: ''
        }
      });
      modalInstance.result.then(function (optOutReason) {
        $scope.fcOpted = true;
        $scope.fcEnabled = false;
        $scope.updateFeatures(optOutReason);
      }, function () {
        // modal cancelled -> todo
      });
    }

    $scope.optInFc = function () {
      $scope.fcOpted = true;
      $scope.fcEnabled = true;
      $scope.updateFeatures();
    }

    function updateFeatures (featureData) {
      var request = $http({
        url: '/features',
        method: 'POST',
        data: featureData
      });
      request.success(function (data) {
        if (data.success) {
          $scope.features = data.data.features;
          $scope.alerts.resetAlerts();
          $scope.optOutReason = '';
          $scope.alerts.addAlert('success', 'Features Updated', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    function init() {
      fetchFeatures();
    }

    init();
  }
]).controller('optOutReasonModalCtrl', [
  '$scope',
  '$modalInstance',
  'optOutReason',
  function ($scope, $modalInstance, optOutReason) {
    $scope.optOutReason = '';
    $scope.ok = function (optOutReason) {
      $modalInstance.close(optOutReason);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
