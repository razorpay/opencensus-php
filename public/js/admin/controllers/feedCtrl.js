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
      makers: ['maker@bank.org'],
      checkers: ['checker1@bank.org', 'checker2@bank.org'],
      feed: [
      {
        type: 'action',
        text: 'maker@bank.org changed the value of merchant_name from Big Bank to Bigger Bank',
        user: 'maker@bank.org',
        timestamp: Date.now()
      },
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
