// Webhooks Ctrl
app.controller('WebhooksCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    $scope.webhooks = {
      items: [],
      count: 0
    };

    $scope.createWebhook = function(webhook) {
      console.debug(webhook);
    }

    $scope.openCreateWebook = function() {
      var modalInstance = $modal.open({
        templateUrl: 'newWebhookModalContent.html',
        controller: 'newWebhookCtrl',
        backdrop: 'static'
      });

      modalInstance.result.then($scope.createWebhook, $.noop);
    }

    $scope.fetchWebhooks = function() {
      var request = $http({
        method: 'get',
        url: '/' + $scope.mode + '/webhooks'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.webhooks.items.push(data.data);
          $scope.webhooks.count = parseInt($scope.webhooks.count) + 1;
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
}]).controller('newWebhookCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.webhook = {
      url: "",
      events: {
        'payment_authorized': true
      }
    };
    $scope.ok = function (webhook) {
      $modalInstance.close(webhook);
    };
  }
]);
