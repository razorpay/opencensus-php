"use strict";

app.controller('FeaturesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$modal',
  function ($scope, $http, alertsFactory, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.fcEnabled = false;

    $scope.toggleFc = function () {
      $scope.fcEnabled = !$scope.fcEnabled;
      updateFeatures();
    }

    function parseAndSetFeatures (features) {
      var noFlashCheckout = features.filter(function (feature) {
        return feature.feature === 'noflashcheckout';
      });
      if (noFlashCheckout.length > 0) {
        noFlashCheckout = noFlashCheckout.shift().value;
      }
      $scope.fcEnabled = !noFlashCheckout;
    }

    function fetchFeatures() {
      var request = $http({
        method: 'get',
        url: '/features'
      });
      request.success(function (data) {
        if (data.success) {
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

    function updateFeatures () {
      var noFlashCheckout = !$scope.fcEnabled ? 1 : 0;

      var featureData = {
        features: {
          noflashcheckout: noFlashCheckout,
        }
      };

      var request = $http({
        url: '/features',
        method: 'POST',
        data: featureData
      });
      request.success(function (data) {
        if (data.success) {
          $scope.features = data.data.features;
          $scope.alerts.resetAlerts();
          $scope.alerts.addAlert('success', 'Your preference was saved', true);
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

    fetchFeatures();
  }
]);
