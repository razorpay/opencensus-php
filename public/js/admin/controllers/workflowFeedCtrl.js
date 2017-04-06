"use strict";
//Entities Listing Controller
app.controller('WorkflowFeedCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin) {

    if (typeof $stateParams.action_id !== 'undefined') {
      $scope.action_id = $stateParams.action_id;
    }

    $scope.fetchDiff = function () {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'action_diff_get',

          url_params: {
            '{id}': $stateParams.action_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.action_diff_data = data;
        }
      });
    };

    $scope.fetchDiff();

    // Audit log breakup details

    $scope.showActionChanges = function () {
      $modal.open({
        templateUrl: 'actionChanges.html',
        controller: 'actionChangeCtrl',
        resolve: {
          action_data: function () {
            return $scope.action_diff_data;
          }
        }
      });
    };

    $scope.feed = {
      actionName: 'edit_merchant_name',
      action_state: 'Pending',
      action_text: 'Edited merchant name from M1 to M2',
      makers: ['maker@bank.org'],
      timeago: moment().from(new Date()),
      timestamp: (new Date()).toString(),
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
    };

  }
])
.controller('actionChangeCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'action_data',
  function($scope, $modalInstance, $http, action_data) {
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };

    $scope.data = action_data;
  }
]);
