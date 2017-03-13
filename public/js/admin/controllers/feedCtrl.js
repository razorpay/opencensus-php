"use strict";
//Entities Listing Controller
app.controller('FeedCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin) {
    $scope.feed = {
      actionName: 'edit_merchant_name',
      action_state: 'Pending',
      action_text: 'Edited merchant name from M1 to M2',
      makers: ['maker@bank.org'],
      timestamp: Date(),
      checkers: ['checker1@bank.org', 'checker2@bank.org'],
      comments: [
      {
        type: 'comment',
        text: 'I think we should wait to implement this change.',
        user: 'checker1@bank.org',
        timestamp: Date.now()
      },
      {
        type: 'comment',
        text: 'Looks good to me.',
        user: 'checker2@bank.org',
        timestamp: Date.now()
      },
      {
        type: 'approval',
        text: 'checker2@bank.org approved the action.',
        user: 'checker2@bank.org',
        timestamp: Date.now()
      }
      ]
    }
  }
]);
