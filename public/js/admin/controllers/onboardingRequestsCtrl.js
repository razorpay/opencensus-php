(function() {
  'use strict';

  app.controller('OnboardingRequestsCtrl', [
    '$scope',
    '$http',
    '$modal',
    '$q',
    '$upload',
    function OnboardingRequestsCtrl($scope, $http, $modal, $q, $upload) {
      var statuses = ($scope.statuses = ['pending', 'rejected', 'approved']);

      var featureDetails = ($scope.featureDetails = {
        subscriptions: { title: 'Subscriptions' },
        marketplace: { title: 'Marketplace' },
        va: {
          title: 'Virtual Accounts',
          aka: 'virtual_accounts',
        },
      });

      var statusFeatMap = ($scope.statusFeatMap = {
        subscriptions_activation_status: featureDetails.subscriptions,
        marketplace_activation_status: featureDetails.marketplace,
        virtual_accounts_activation_status: featureDetails.va,
      });

      var transferToDDVals = ($scope.transferToDDVals = [
        ['Businesses', 'Third-party businesses'],
        ['Own Accounts', 'Own bank accounts'],
        ['Individuals', 'Individuals'],
      ]);

      function addErrors(errors) {
        angular.forEach(errors, function(value) {
          $scope.alerts.addAlert('danger', value);
        });
      }

      $scope.selectedStatus = $scope.statuses[0];

      $scope.onboardingSubmissions = [];

      $scope.fetchSubmissions = function fetchRequests(status) {
        var data = {
          route_name: 'onboarding_features_fetch_submissions',
          query_params: { status: status },
        };

        $http
          .get('/admin/generic', { params: data })
          .success(function onFetchRequestsSuccess(data) {
            if (data.success) {
              $scope.onboardingSubmissions = [];

              angular.forEach(data.data, function(record) {
                angular.forEach(statusFeatMap, function(data, key) {
                  var featureName = data.title,
                    featureFetchName = data.aka || data.title.toLowerCase();

                  if (record[key] === $scope.selectedStatus) {
                    $scope.onboardingSubmissions.push({
                      merchant: record.merchant_id,
                      feature: featureName,
                      featureFetchName: featureFetchName,
                      status: $scope.selectedStatus,
                      responses: null,
                      editVal: null,
                    });
                  }
                });
              });
            } else {
              addErrors(data.errors);
            }
          })
          .error(function onFetchRequestsError(res) {
            $scope.alerts.addAlert('danger', res ? res : null, true);
          });
      };

      $scope.onSaveFeature = function onSaveFeature(submission, form) {
        var editVal = submission.editVal,
          status = editVal.status,
          responses = editVal.responses;

        submission.editVal = null;

        var requests = [],
          request,
          data,
          config;

        if (submission.status !== status) {
          data = {
            route_name: 'onboarding_features_update_status',
            body: {
              status: status,
              merchant_id: submission.merchant,
            },
            url_params: {
              '{feature}': submission.featureFetchName,
            },
          };

          request = $http.put('/admin/generic', data);

          request
            .success(function(data) {
              if (data.success) {
                submission.status = status;
              } else {
                addErrors(data.errors);
              }
            })
            .error(function onFetchResponsesError(res) {
              $scope.alerts.addAlert('danger', res ? res : null, true);
            });

          requests.push(request);
        }

        data = {
          route_name: 'onboarding_features_update',
        };

        if (submission.feature === featureDetails.marketplace.title) {
          angular.forEach(responses, function(val, key) {
            if (key === 'file' || key === 'file_name') {
              data[key] = val;
            } else {
              data['body[' + key + ']'] = val;
            }
          });

          data['body[merchant_id]'] = submission.merchant;
          data['url_params[{feature}]'] = submission.featureFetchName;

          data = {
            method: 'POST',
            url: '/admin/generic',
            data: data,
          };

          request = $upload.upload(data);
        } else {
          data.body = responses;
          data.body.merchant_id = submission.merchant;

          data.url_params = {
            '{feature}': submission.featureFetchName,
          };

          request = $http.put('/admin/generic', data);
        }

        request
          .success(function(data) {
            if (data.success) {
              addErrors(data.errors);
            }
          })
          .error(function onFetchResponsesError(res) {
            $scope.alerts.addAlert('danger', res ? res : null, true);
          });

        requests.push(request);

        return $q.all(requests);
      };

      $scope.onCancelEditFeature = function onCancelEditFeature(submission) {
        submission.editVal = null;
      };

      $scope.openUpdateSubmission = function(submission, onSave, onCancel) {
        var $parentScope = $scope;

        $modal.open({
          templateUrl: 'updateSubmission.html',
          controller: [
            '$scope',
            '$modalInstance',
            '$upload',
            function($scope, $modalInstance) {
              $scope.submission = submission;
              $scope.statuses = statuses;
              $scope.featureDetails = featureDetails;
              $scope.transferToDDVals = transferToDDVals;

              $scope.onFileSelect = function onFileSelect($files, submission) {
                var file = ($scope.submission.editVal.responses.file =
                  $files[0]);
                $scope.submission.editVal.responses.file_name = file.name;
              };

              $scope.ok = function onSubmit(submission, form) {
                onSave(submission, form).then(function() {
                  $modalInstance.close();
                });
              };

              $scope.cancel = function cancel() {
                onCancel(submission);
                $modalInstance.dismiss('cancel');
              };
            },
          ],
        });
      };

      $scope.onEditFeature = function onEditFeature(submission) {
        submission.editVal = {
          status: submission.status,
          responses: null,
        };

        var featureFetchName = submission.featureFetchName;

        var data = {
          route_name: 'onboarding_features_fetch_details',
          query_params: { features: [featureFetchName] },
          merchant_id: submission.merchant,
        };

        $http
          .get('/admin/generic', { params: data })
          .success(function onFetchResponsesSuccess(data) {
            if (data.success) {
              var responses = data.data.submissions[featureFetchName];
              submission.responses = submission.editVal.responses = responses;
            } else {
              addErrors(data.errors);
            }
          })
          .error(function onFetchResponsesError(res) {
            $scope.alerts.addAlert('danger', res ? res : null, true);
          });

        $scope.openUpdateSubmission(
          submission,
          $scope.onSaveFeature.bind($scope),
          $scope.onCancelEditFeature.bind($scope)
        );
      };

      $scope.fetchSubmissions($scope.selectedStatus);
    },
  ]);
})();
