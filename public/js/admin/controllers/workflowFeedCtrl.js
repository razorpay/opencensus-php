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

    $scope.saveComment = function () {
      var body = {
        comment: $scope.new_comment
      };

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'action_comment_create',
          url_params: {
            '{id}': $stateParams.action_id
          }
        },
        data: {
          body: body
        }
      });

      request.success(function (data) {
        if (data.success) {
          commentMod(data.data);

          $scope.cards.push(data.data);

          $scope.new_comment = null;

          $scope.comment_count += 1;
        }
      })
    };

    // Change passed object by reference
    var commentMod = function (comment) {
      comment.type = 'comment';

      // Resolve name
      var name = comment.admin.name ? comment.admin.name : (!comment.admin.username ? comment.admin.username : comment.admin.email);
      comment.admin_name = name;
    };

    $scope.fetchAllComments = function () {
      var request = $http({
        url: '/admin/generic',
        method: 'GET',
        params: {
          route_name: 'action_comment_fetch',
          url_params: {
            '{id}': $stateParams.action_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          data.data.items.forEach(function (item, k) {
            commentMod(item);

            data.data.items[k] = item;
          });

          $scope.cards = $scope.cards.concat(data.data.items);

          $scope.comment_count = data.data.count;
        }
      });
    };

    $scope.fetchAllComments();

    $scope.fetchActionDetails = function () {
      var request = $http({
        url: '/admin/generic',
        method: 'GET',
        params: {
          route_name: 'workflow_action_details',
          url_params: {
            '{id}': $stateParams.action_id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.action_details = data.data;
        }
      });
    };

    $scope.fetchActionDetails();

    // Cards data

    $scope.cards = [];

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
