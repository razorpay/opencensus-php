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
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, user) {
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

      var request = $http({
        method: 'post',
        url: '/' + $scope.mode + '/webhooks',
        //transformRequest: transformRequestAsFormPost,
        data: webhook
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
        events: webhook.events,
        active: webhook.active,
        url: webhook.url
      };

      if (webhook.secret !== '') {
        payload.secret = webhook.secret;
      }

      var request = $http({
        method: 'put',
        url: '/' + $scope.mode + '/webhooks/' + webhook.id,
        data: payload
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
        backdrop: 'static',
        resolve: {
          showInvoice: function () {
            return ($scope.user.tags.indexOf('Invoice') > -1);
          }
        }
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
          },
          showInvoice: function () {
            return ($scope.user.tags.indexOf('Invoice') > -1);
          }
        }
      });

      modalInstance.result.then($scope.editWebhook, $.noop);
    };

    $scope.fetchWebhooks = function() {
      var request = $http({
        method: 'get',
        url: '/' + $scope.mode + '/webhooks'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.webhooks.items = data.data.items;
          $scope.webhooks.count = data.data.count;
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
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
  'showInvoice',
  function ($scope, $modalInstance, showInvoice) {
    $scope.showInvoice = showInvoice;
    $scope.webhook = {
      url: "",
      events: {
        'payment.authorized': false,
        'payment.failed': false,
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
  'showInvoice',
  function ($scope, $modalInstance, webhook, showInvoice) {
    $scope.showInvoice = showInvoice;
    $scope.webhook = webhook;
    $scope.ok = function (webhook) {
      $modalInstance.close(webhook);
    };

    $scope.cancel = function (webhook) {
      $modalInstance.dismiss('cancel');
    };
  }
]);
