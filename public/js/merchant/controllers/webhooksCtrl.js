"use strict";
/**
 * Webhooks Ctrl
 */
app.controller('WebhooksCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'user',
  'utils',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, user, utils) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    user.identity().then(function (data) {
      $scope.user = data;
    });

    $scope.webhooks = {
      items: [],
      count: 0
    };

    $scope.countActiveEvents = function (webhook) {
      return Object.keys(webhook.events).filter(function (eventKey) {
        return webhook.events[eventKey] === true;
      }).length;
    };

    $scope.createWebhook = function(webhook) {

      var params = {};
      params.route_name = 'webhook_create';
      params.mode = $scope.mode;
      webhook.events = utils.convertBoolToString(webhook.events);
      params.body = webhook;

      var request = $http({
        url: '/generic',
        method: 'POST',
        data: params
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Webhook added', true);

          // Update the entire list
          $scope.fetchWebhooks();
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

    $scope.editWebhook = function(webhook) {

      // This is the whitelist of things you
      // can edit in a webhook

      var payload = {
        events: utils.convertBoolToString(webhook.events),
        active: utils.convertBoolToString(webhook.active),
        url: webhook.url
      };

      if (webhook.secret !== '') {
        payload.secret = webhook.secret;
      }

      var params = {};
      params.route_name = 'webhook_edit';
      params.mode = $scope.mode;
      params.url_params = {
        '{id}': webhook.id
      };
      params.body = payload;

      var request = $http({
        url: '/generic',
        method: 'PUT',
        data: params
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Webhook edited', true);

          // Update the entire list
          $scope.fetchWebhooks();
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

    $scope.openCreateWebook = function() {
      var modalInstance = $modal.open({
        templateUrl: 'newWebhookModalContent.html',
        controller: 'newWebhookCtrl',
        backdrop: 'static'
      });

      modalInstance.result.then($scope.createWebhook, $.noop);
    };

    $scope.openEditWebhook = function(data) {
      var modalInstance = $modal.open({
        templateUrl: 'editWebhookModalContent.html',
        controller: 'editWebhookCtrl',
        backdrop: 'static',
        resolve: {
          webhook: function () {
            return data;
          }
        }
      });

      modalInstance.result.then($scope.editWebhook, $.noop);
    };

    $scope.fetchWebhooks = function() {
      var params = {};
      params.route_name = 'webhook_fetch_multiple';
      params.mode = $scope.mode;

      var request = $http.get('/generic', {
        params: params
      });

      request.success(function (data) {
        if (data.success) {
          $scope.webhooks.items = data.data.items;
          $scope.webhooks.count = data.data.count;
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

    // Fetch the webhooks now
    $scope.fetchWebhooks();
}]).controller('newWebhookCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {
    $scope.webhook = {
      url: "",
      events: {
        'payment.authorized': false,
        'payment.failed': false,
        'order.paid': false,
        'invoice.paid': false
      }
    };
    $scope.ok = function (webhook) {
      $modalInstance.close(webhook);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('editWebhookCtrl', [
  '$scope',
  '$modalInstance',
  'webhook',
  function ($scope, $modalInstance, webhook) {
    $scope.webhook = webhook;
    $scope.ok = function (webhook) {
      $modalInstance.close(webhook);
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
