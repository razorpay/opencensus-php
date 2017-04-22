'use strict';
//Entities Listing Controller
app
  .controller('WorkflowFeedCtrl', [
    '$scope',
    '$http',
    'alertsFactory',
    '$state',
    '$modal',
    '$stateParams',
    'admin',
    function(
      $scope,
      $http,
      alertsFactory,
      $state,
      $modal,
      $stateParams,
      admin
    ) {
      $scope.alerts = alertsFactory.getHandler();

      if (typeof $stateParams.action_id !== 'undefined') {
        $scope.action_id = $stateParams.action_id;
      }

      $scope.fetchDiff = function() {
        var request = $http.get('/admin/generic', {
          params: {
            route_name: 'action_diff_get',

            url_params: {
              '{id}': $stateParams.action_id,
            },
          },
        });

        request.success(function(data) {
          if (data.success) {
            $scope.action_diff_data = data;
          }
        });
      };

      $scope.fetchDiff();

      // Audit log breakup details

      $scope.showActionChanges = function() {
        $modal.open({
          templateUrl: 'actionChanges.html',
          controller: 'actionChangeCtrl',
          resolve: {
            action_data: function() {
              return $scope.action_diff_data;
            },
          },
        });
      };

      $scope.saveComment = function() {
        var body = {
          comment: $scope.new_comment,
        };

        var request = $http({
          url: '/admin/generic',
          method: 'POST',
          params: {
            route_name: 'action_comment_create',
            url_params: {
              '{id}': $stateParams.action_id,
            },
          },
          data: {
            body: body,
          },
        });

        request.success(function(data) {
          if (data.success) {
            commentMod(data.data, 'comment');

            $scope.cards.push(data.data);

            $scope.new_comment = null;

            $scope.comment_count += 1;
          }
        });
      };

      // Change passed object by reference
      var commentMod = function(comment, type) {
        comment.type = type;

        // Resolve name
        var name = comment.admin.name
          ? comment.admin.name
          : !comment.admin.username
              ? comment.admin.username
              : comment.admin.email;
        comment.admin_name = name;
      };

      // $scope.fetchAllComments = function () {
      //   var request = $http({
      //     url: '/admin/generic',
      //     method: 'GET',
      //     params: {
      //       route_name: 'action_comment_fetch',
      //       url_params: {
      //         '{id}': $stateParams.action_id
      //       }
      //     }
      //   });
      //
      //   request.success(function (data) {
      //     if (data.success) {
      //       data.data.items.forEach(function (item, k) {
      //         commentMod(item);
      //
      //         data.data.items[k] = item;
      //       });
      //
      //       $scope.cards = $scope.cards.concat(data.data.items);
      //
      //       $scope.comment_count = data.data.count;
      //     }
      //   });
      // };
      //
      // $scope.fetchAllComments();

      $scope.fetchActionDetails = function() {
        var request = $http({
          url: '/admin/generic',
          method: 'GET',
          params: {
            route_name: 'workflow_action_details',
            url_params: {
              '{id}': $stateParams.action_id,
            },
          },
        });

        request.success(function(data) {
          if (data.success) {
            // Action details
            $scope.action_details = data.data;

            // Action comments

            var comments = data.data.comments;

            comments.forEach(function(item, k) {
              commentMod(item, 'comment');

              comments[k] = item;
            });

            $scope.cards = $scope.cards.concat(comments);

            $scope.comment_count = comments.length;

            // Action approvals/rejections

            var checkers = data.data.checkers;

            checkers.forEach(function(item, k) {
              commentMod(item, 'status');

              checkers[k] = item;
            });

            $scope.cards = $scope.cards.concat(checkers);

            $scope.approverList = $scope.cards.filter(function(card) {
              return card.approved;
            });

            $scope.rejectorList = $scope.cards.filter(function(card) {
              return !card.approved;
            });
          }
        });
      };

      $scope.fetchActionDetails();

      // Cards data

      $scope.cards = [];

      $scope.actionStateChange = function(state) {
        var body = {};
        var route_name;
        var method = 'POST';

        if (state === 'approve') {
          body = {
            approved: 1,
          };

          route_name = 'action_checker_create';
        }

        if (state === 'reject') {
          body = {
            approved: 0,
          };

          route_name = 'action_checker_create';
        }

        if (state === 'close') {
          route_name = 'workflow_action_close';
          method = 'PUT';
        }

        if (state === 'execute') {
          route_name = 'action_request_execute';
        }

        if (typeof route_name !== 'undefined') {
          var request = $http({
            url: '/admin/generic',
            method: method,

            params: {
              route_name: route_name,

              url_params: {
                '{id}': $stateParams.action_id,
              },
            },

            data: {
              body: body,
            },
          });

          request
            .success(function(data) {
              if (data.success) {
                $scope.alerts.addAlert(
                  'success',
                  'Action performed successfully!',
                  true
                );
                $scope.action_details.state = data.data.state; // Change state of request
              } else {
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function(errors) {
              angular.forEach(errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            });
        }
      };

      $scope.titleActive = false;
      $scope.descActive = false;
      $scope.titleStatus = '';
      $scope.descStatus = '';
      $scope.setTitleActive = function() {
        $scope.titleActive = true;
        $('#title-input').focus();
      };
      $scope.setTitleInactive = function() {
        $scope.titleActive = false;
      };
      $scope.setDescActive = function() {
        $scope.descActive = true;
        $('#desc-textarea').focus();
      };
      $scope.setDescInactive = function() {
        $scope.descActive = false;
      };

      $scope.saveTitle = function() {
        var title = $scope.action_details.title;
        $scope.alerts.resetAlerts();
        if (!title) {
          $scope.setTitleInactive();
          $scope.alerts.addAlert('danger', 'Please enter a title');
          return;
        }
        $scope.titleStatus = 'saving';
        var request = $http({
          url: '/admin/generic',
          method: 'PUT',
          params: {
            route_name: 'workflow_action_update',
            url_params: {
              '{id}': $stateParams.action_id,
            },
          },
          data: {
            body: {
              title: title,
            },
          },
        });

        request
          .success(function(data) {
            $scope.titleStatus = '';

            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Title changed successfully!',
                true
              );
              $scope.setTitleInactive();
            } else {
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function(errors) {
            angular.forEach(errors, function(value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          });
      };

      $scope.saveDesc = function() {
        var desc = $scope.action_details.description;
        $scope.alerts.resetAlerts();
        if (!desc) {
          $scope.setDescInactive();
          $scope.alerts.addAlert('danger', 'Please enter a description');
          return;
        }
        $scope.descStatus = 'saving';
        var request = $http({
          url: '/admin/generic',
          method: 'PUT',
          params: {
            route_name: 'workflow_action_update',
            url_params: {
              '{id}': $stateParams.action_id,
            },
          },
          data: {
            body: {
              description: desc,
            },
          },
        });

        request
          .success(function(data) {
            $scope.descStatus = '';
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Description changed successfully!',
                true
              );
              $scope.setDescInactive();
            } else {
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function(errors) {
            angular.forEach(errors, function(value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          });
      };
    },
  ])
  .controller('actionChangeCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'action_data',
    'utils',
    function($scope, $modalInstance, $http, action_data, utils) {
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };

      $scope.data = action_data;
      $scope.isArray = utils.isArray;
      $scope.dataKeys = [];

      if ($scope.data && $scope.data.success) {
        var oldData = $scope.data.data.old ? $scope.data.data.old : [];
        var newData = $scope.data.data.new ? $scope.data.data.new : [];
        $scope.dataKeys = utils.mergeUnique(
          Object.keys(oldData).concat(Object.keys(newData))
        );
      }
    },
  ]);
