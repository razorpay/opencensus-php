'use strict';
// Merchant Details Controller
app
  .controller('MerchantDetailCtrl', [
    '$scope',
    '$http',
    '$stateParams',
    'alertsFactory',
    'transformRequestAsFormPost',
    '$modal',
    'riskMap',
    'admin',
    '$upload',
    'organization',
    'displayValue',
    'utils',
    'utilMapping',
    '$state',
    function(
      $scope,
      $http,
      $stateParams,
      alertsFactory,
      transformRequestAsFormPost,
      $modal,
      riskMap,
      admin,
      $upload,
      organization,
      displayValue,
      utils,
      utilMapping,
      $state
    ) {
      admin.identity().then(function(data) {
        $scope.admin = data;
      });

      $scope.riskMap = riskMap;
      $scope.displayValue = displayValue;
      // 5 Lac INR
      $scope.DEFAULT_MAX_PAYMENT_AMOUNT = 50000000;
      $scope.alerts = alertsFactory.getHandler();
      $scope.merchant = {
        id: $stateParams.id,
        balance: {
          test: 0,
          live: 0,
        },
      };
      $scope.showMerchantBatchUpload = false;

      admin.identity().then(function(adminData) {
        if (adminData.permissions.indexOf('view_all_group') !== -1) {
          organization.fetchGroups().then(function(groups) {
            $scope.groups = groups;
          });
        }
      });

      $scope.networkMap = utilMapping.getMap('networkMap');
      $scope.methodMap = utilMapping.getMap('methodMap');
      $scope.selected_groups = {};

      generateMerchant();
      getOffersOfMerchant();
      getGatewayRulesOfMerchant();

      // Gateway map is dependent upon method
      $scope.getGatewayLabel = function(method, gateway) {
        var map = null;
        switch (method) {
          case 'card':
            map = utilMapping.getMap('gatewayCardMap');
            break;
          case 'emi':
            map = utilMapping.getMap('gatewayEmiMap');
            break;
          case 'netbanking':
            map = utilMapping.getMap('gatewayNBMap');
            break;
          case 'wallet':
            map = utilMapping.getMap('gatewayWalletMap');
            break;
          case 'upi':
            map = utilMapping.getMap('gatewayUpiMap');
            break;
        }

        if (!map) {
          return gateway;
        }

        return map[gateway];
      };

      $scope.lockForm = function() {
        var data = {
          route_name: 'merchant_activation_update',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: {
            locked: true,
          },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant Form locked successfully',
                true
              );
              $scope.merchant.details.locked = 1;
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.setInternational = function(value) {
        var action = '';
        var succesMsg = '';
        if (value === 1) {
          action = 'enable_international';
          succesMsg = 'Merchant International enabled successfully';
        } else if (value === 0) {
          action = 'disable_international';
          succesMsg = 'Merchant International disabled successfully';
        }
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: action },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert('success', succesMsg, true);

              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.captureScreenshot = function() {
        var request = $http.put(
          '/admin/merchant/' + $scope.merchant.id + '/screenshot'
        );
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Website screenshots capture started. Wait for notification on Slack',
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      var getReferer = function(tags) {
        for (var i in tags) {
          var tag = tags[i];
          if (tag.substr(0, 4).toLowerCase() === 'ref-') {
            return tag.substr(4);
          }
        }
        return '';
      };

      $scope.tagMerchant = function(tags) {
        var data = {
          route_name: 'merchant_tag_add',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: {
            tags: tags,
          },
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant tagged successfully.',
                true
              );
              $scope.merchant.details.tags = data.data;
              $scope.referer = getReferer(data.data);
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.markMerchantAsReferred = function(referral) {
        var tags = $scope.merchant.details.tags;
        tags.push('ref-' + referral);
        $scope.tagMerchant(tags);
      };

      $scope.featureMerchant = function(features, mode, shouldSync) {
        // Tags will be a csv field
        var requestData = {
          features: features,
          mode: mode,
        };
        if (shouldSync === true) {
          requestData['mode'] = 'live';
          requestData['should_sync'] = 1;
        } else {
          requestData['should_sync'] = 0;
        }
        var request = $http({
          url: '/admin/features/merchant/' + $scope.merchant.id,
          method: 'POST',
          transformRequest: transformRequestAsFormPost,
          data: requestData,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Features have been added successfully.',
                true
              );
              // When shouldSync is true, requestData.mode will be live but mode can be test/live
              $scope.merchant.details.allowedFeatures[requestData.mode] =
                data.data.all_features;
              $scope.merchant.details.features[
                requestData.mode
              ] = getFeatureNames(data.data.assigned_features);
              // If features were added to both, the response will have features for live
              // So fetch all test features again.
              if (shouldSync === true) {
                $scope.getModeBasedFeatures('test');
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.unlockForm = function() {
        var data = {
          route_name: 'merchant_activation_update',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: {
            locked: false,
          },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant Form unlocked successfully',
                true
              );
              $scope.merchant.details.locked = 0;
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.activateMerchant = function(dashboard) {
        var query = {};

        if (typeof dashboard !== 'undefined') {
          query.dashboard = true;
        }

        var request = $http.get(
          '/admin/merchant/' + $scope.merchant.id + '/activate',
          {
            params: query,
          }
        );
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant Activated successfully',
                true
              );
              $scope.merchant.details.activated = 1;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.holdMerchantFunds = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'hold_funds' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant funds put on hold successfully',
                true
              );

              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.releaseMerchantFunds = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'release_funds' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant funds released successfully',
                true
              );

              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.enableLive = function() {
        var data = {
          route_name: 'merchant_live_enable',
          url_params: {
            '{id}': $scope.merchant.id,
          },
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Live transactions for merchant enabled successfully',
                true
              );
              $scope.merchant.details.live = 1;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.disableLive = function() {
        var data = {
          route_name: 'merchant_live_disable',
          url_params: {
            '{id}': $scope.merchant.id,
          },
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Live transactions for merchant disabled successfully',
                true
              );
              $scope.merchant.details.live = 0;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.editMethods = function(methods, msg) {
        var postMethods = {};
        for (var i in methods) {
          postMethods[i] = methods[i] ? 1 : 0;
        }

        msg = typeof msg !== 'undefined'
          ? msg
          : 'Methods edited successfully: ' + JSON.stringify(methods);

        var data = {
          route_name: 'merchant_put_payment_methods',
          url_params: {
            '{mid}': $scope.merchant.id,
          },
          body: postMethods,
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert('success', msg, true);
                $scope.merchant.details.methods = $.extend(
                  $scope.merchant.details.methods,
                  methods
                );
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.setReceiptEmail = function(value) {
        var action = '';
        var succesMsg = '';
        if (value === 1) {
          action = 'enable_receipt_emails';
          succesMsg = 'Receipt email enabled successfully';
        } else if (value === 0) {
          action = 'disable_receipt_emails';
          succesMsg = 'Receipt email disabled successfully';
        }
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: action },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert('success', succesMsg, true);

              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.assignPricing = function(data) {
        data = { pricing_plan_id: data.id, pricing_plan_name: data.name };

        var pricingData = {
          route_name: 'merchant_assign_pricing',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: data,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: pricingData,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert(
                  'success',
                  'Plan Assigned successfully',
                  true
                );
                $scope.merchant.pricing_plan = data.data;
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      // Prepare report
      $scope.prepareReport = function(reportOptions) {
        var entity = reportOptions.entity;
        var type = reportOptions.type;
        var month = reportOptions.month;
        var year = reportOptions.year;
        var day = reportOptions.day;

        var data = {
          month: month,
          year: year,
        };

        // Open new window if entity type is 'invoice'
        if (entity === 'invoice') {
          return Promise.resolve(
            window.open(
              '/admin/live/reports/invoice?year=' +
                year +
                '&month=' +
                month +
                '&merchant_id=' +
                $scope.merchant.id,
              '_blank'
            )
          );
        }

        if (type === 'daily') {
          data.day = day;
        }

        var ajaxParams = {
          params: data,
        };

        if (entity === 'broking') {
          ajaxParams.headers = {
            Accept:
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          };
        }

        var request = $http.get(
          '/admin/live/reports/' +
            entity +
            '?merchant_id=' +
            $scope.merchant.id,
          ajaxParams
        );

        request
          .success(function(data) {
            $scope.alerts.addAlert(
              'success',
              'Your report will download shortly',
              true
            );

            if (entity === 'broking') {
              var blob = new Blob([data], {
                type:
                  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              });
              return saveAs(blob, 'broking_report.xlsx');
            }

            location.href = data.data.url;
          })
          .error(function() {
            $scope.alerts.addAlert(
              'danger',
              'No data found for given time range',
              true
            );
          });
      };

      $scope.assignSchedule = function(data) {
        var data = {
          route_name: 'schedule_assign',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: data,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert(
                  'success',
                  'Schedule Assigned successfully',
                  true
                );
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.assignTerminal = function(terminal, modalInstance, alerts) {
        var requestData = {
          method: 'post',
          url: '/admin/merchant/' + $scope.merchant.id + '/terminal',
          data: terminal,
        };
        if (typeof terminal.gateway_client_certificate !== 'undefined') {
          requestData.file = terminal.gateway_client_certificate;
        }
        var request = $upload.upload(requestData);
        request
          .success(function(data) {
            if (data.success) {
              terminal.id = data.data.id;
              terminal.created_at = data.data.created_at;
              $scope.merchant.terminals.items.push(terminal);
              $scope.merchant.terminals.count =
                $scope.merchant.terminals.count + 1;
              modalInstance.close();
            } else {
              alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            alerts.addAlert('danger', null, true);
          });
      };

      $scope.assignMerchantHandle = function(handle) {
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: {
            route_name: 'merchant_edit_config',
            merchant_id: $scope.merchant.id,
            body: {
              handle: handle,
            },
          },
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant handle saved successfully',
                true
              );
              generateMerchant();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.assignBanks = function(bankdata) {
        var data = { banks: [] };
        angular.forEach(bankdata, function(i, e) {
          if (i === true) {
            data.banks.push(e);
          }
        });
        var data = {
          route_name: 'merchant_set_banks',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: data,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert(
                  'success',
                  'Banks Assigned successfully',
                  true
                );
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.addAdjustment = function(adjustment) {
        var mode = adjustment.mode;
        delete adjustment.mode;

        adjustment.merchant_id = $scope.merchant.id;

        var data = {
          route_name: 'adj_add',
          merchant_id: $scope.merchant.id,
          body: adjustment,
          mode: mode,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert(
                  'success',
                  'Adjustment added successfully',
                  true
                );
                fetchBalance();
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      /**
     * Sends the final edit merchant ajax call
     * @param  Object merchant
     */
      $scope.editMerchant = function(
        merchant,
        selected_groups,
        selected_admins
      ) {
        // If the second parameter was not provided
        // we don't try to edit the groups and don't
        // send the field instead.
        for (var key in selected_groups) {
          if (selected_groups.hasOwnProperty(key)) {
            if (!selected_groups[key]) {
              delete selected_groups[key];
            }
          }
        }

        if (typeof selected_groups === 'undefined') {
          selected_groups = {};
        } else {
          merchant.groups = Object.keys(selected_groups);
        }

        if (typeof selected_admins === 'undefined') {
          selected_admins = [];
        } else {
          merchant.admins = selected_admins;
        }

        var dropUnchangedFields = function(merchant) {
          for (var i in merchant) {
            var val = $scope.merchant.details[i];
            if (val && Array === val.constructor) {
              val = val.join(',');
            }

            // Since merchant[i] is what is being sent in the form
            // it will always be a string, we ensure above that
            // any arrays are converted to string before we match them
            //
            // This is primarily to compare the transaction_report_email field
            if (merchant[i] === val) {
              delete merchant[i];
            }
          }
        };

        dropUnchangedFields(merchant);

        var request = $http({
          method: 'post',
          url: '/admin/merchant/' + $scope.merchant.id + '/edit',
          data: angular.toJson(merchant),
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant edited successfully',
                true
              );
              generateMerchant();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.editMerchantEmail = function(email) {
        var request = $http({
          method: 'put',
          url: '/admin/merchant/' + $scope.merchant.id + '/email',
          data: { email: email },
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant email edited successfully',
                true
              );
              generateMerchant();
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.changeBankAccountDetails = function(bankAccount) {
        delete bankAccount.beneficiary_address4;
        delete bankAccount.beneficiary_code;
        delete bankAccount.beneficiary_country;
        delete bankAccount.created_at;
        delete bankAccount.entity_id;
        delete bankAccount.type;
        delete bankAccount.id;
        delete bankAccount.merchant_id;
        delete bankAccount.mpin_set;
        delete bankAccount.ifsc;
        delete bankAccount.name;
        delete bankAccount.bank_name;

        var data = {
          route_name: 'merchant_add_bank_account',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: bankAccount,
        };
        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost,
        });
        request
          .success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.merchant.details.merchant_details = data.data;
                $scope.alerts.addAlert(
                  'success',
                  'Merchant bank details changed successfully',
                  true
                );
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.editComment = function(new_comment) {
        var data = {
          route_name: 'merchant_activation_update',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: {
            comment: new_comment,
          },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant comment edited successfully',
                true
              );
              $scope.merchant.details.merchant_details.comment = new_comment;
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.archiveMerchant = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'archive' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant archived successfully',
                true
              );

              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.merchant.details.archived_at = Date.now() / 1000;
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.unarchiveMerchant = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'unarchive' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant unarchived successfully',
                true
              );
              $scope.merchant.details.archived_at = null;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.suspendMerchant = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'suspend' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant suspended successfully',
                true
              );
              $scope.merchant.details.suspended_at = Date.now() / 1000;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      $scope.unsuspendMerchant = function() {
        var data = {
          route_name: 'merchant_action',
          url_params: {
            '{id}': $scope.merchant.id,
          },
          body: { action: 'unsuspend' },
        };
        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Merchant unsuspended successfully',
                true
              );
              $scope.merchant.details.suspended_at = null;

              // Redirect to details page
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      // Assign pricing modal
      $scope.openAssignPricing = function() {
        var currentPlan = $scope.merchant.pricing_plan.id || '';
        // Switch the default plan to Promotional Pricing
        if (currentPlan === '') {
          currentPlan = '1In3Yh5Mluj605';
        }

        var modalInstance = $modal.open({
          templateUrl: 'assignPricingModalContent.html',
          controller: 'assignPricingModalCtrl',
          resolve: {
            current: function() {
              return currentPlan;
            },
          },
        });

        modalInstance.result.then(function(data) {
          $scope.assignPricing(data);
        }, $.noop);
      };

      $scope.openAssignSchedule = function() {
        var currentSchedule = '';
        var currentType = '';
        var currentMethod = '';

        var modalInstance = $modal.open({
          templateUrl: 'assignScheduleModalContent.html',
          controller: 'assignScheduleModalCtrl',
          resolve: {
            current: function() {
              return {
                type: currentType,
                schedule: currentSchedule,
                method: currentMethod,
              };
            },
          },
        });

        modalInstance.result.then(function(schedule_data) {
          $scope.assignSchedule(schedule_data);
        }, $.noop);
      };

      $scope.openTagMerchant = function() {
        var tags = $scope.merchant.details.tags || [];
        var modalInstance = $modal.open({
          templateUrl: 'tagModalContent.html',
          controller: 'tagModalCtrl',
          resolve: {
            current: function() {
              return tags;
            },
          },
        });
        modalInstance.result.then(function(tags) {
          if (tags) {
            tags = tags.split(',');
          } else {
            tags = [];
          }
          $scope.tagMerchant(tags);
        }, $.noop);
      };

      $scope.openReferralTagModal = function() {
        var modalInstance = $modal.open({
          templateUrl: 'tagReferralContent.html',
          controller: 'referralModalCtrl',
          resolve: {
            current: function() {
              return getReferer();
            },
          },
        });
        modalInstance.result.then(function(referral) {
          $scope.markMerchantAsReferred(referral);
        }, $.noop);
      };

      $scope.openFeatureMerchant = function() {
        var features = {};
        features.test = [];
        features.live = [];
        if ($scope.merchant.details.features) {
          features.test = $scope.merchant.details.features.test || [];
        }
        features.test = features.test.map(function(f) {
          return f.name;
        });
        if ($scope.merchant.details.features) {
          features.live = $scope.merchant.details.features.live || [];
        }
        features.live = features.live.map(function(f) {
          return f.name;
        });
        var allowedFeatures = {};
        allowedFeatures.test =
          $scope.merchant.details.allowedFeatures.test || [];
        allowedFeatures.live =
          $scope.merchant.details.allowedFeatures.live || [];
        // availableFeatures is a list of features which are not yet assigned to
        // the merchant. Used to populate the features dropdown
        var availableFeatures = {};
        availableFeatures.test = allowedFeatures.test.filter(function(f) {
          return features.test.indexOf(f) === -1;
        });
        availableFeatures.live = allowedFeatures.live.filter(function(f) {
          return features.live.indexOf(f) === -1;
        });
        var modalInstance = $modal.open({
          templateUrl: 'featureModalContent.html',
          controller: 'featureModalCtrl',
          resolve: {
            current: function() {
              return [availableFeatures, features];
            },
          },
        });
        modalInstance.result.then(function(data) {
          $scope.featureMerchant(data.features, data.mode, data.shouldSync);
        }, $.noop);
      };
      $scope.openAssignTerminal = function() {
        var modalInstance = $modal.open({
          templateUrl: 'assignTerminalModalContent.html',
          controller: 'assignTerminalModalCtrl',
          resolve: {
            assignTerminal: function() {
              return $scope.assignTerminal;
            },
          },
        });
        modalInstance.result.then(function() {
          $scope.alerts.addAlert(
            'success',
            'Terminal Assigned successfully',
            true
          );
        }, $.noop);
      };
      $scope.openEditMethods = function() {
        var modalInstance = $modal.open({
          templateUrl: 'editMerchantMethods.html',
          controller: 'editMerchantMethodsCtrl',
          resolve: {
            methods: function() {
              return $scope.merchant.details.methods || {};
            },
          },
        });
        modalInstance.result.then(function(methods) {
          $scope.editMethods(methods);
        }, $.noop);
      };
      $scope.openEditMerchant = function() {
        var modalInstance = $modal.open({
          templateUrl: 'editMerchantModalContent.html',
          controller: 'editMerchantModalCtrl',
          resolve: {
            current: function() {
              // Return a copy of current merchant details
              // instead of returning a reference
              return jQuery.extend({}, $scope.merchant.details);
            },
            groups: function() {
              return jQuery.extend({}, $scope.groups);
            },
            organization: function() {
              return jQuery.extend({}, organization);
            },
            selected_groups: function() {
              return $scope.selected_groups;
            },
            selected_admins: function() {
              return $scope.selected_admins;
            },
            permissions: function() {
              return $scope.admin.permissions;
            },
          },
        });
        modalInstance.result.then(function(merchant) {
          $scope.editMerchant(
            merchant,
            $scope.selected_groups,
            $scope.selected_admins
          );
        }, $.noop);
      };

      $scope.openCreateOffer = function() {
        var modalInstance = $modal.open({
          templateUrl: 'createMerchantOfferContent.html',
          controller: 'createMerchantOfferModalCtrl',
          backdrop: 'static',
        });
        modalInstance.result.then(function(data) {
          $scope.createMerchantOffer(data.offer, data.mode);
        }, $.noop);
      };

      $scope.openEditMerchantEmail = function() {
        var modalInstance = $modal.open({
          templateUrl: 'editMerchantEmailModalContent.html',
          controller: 'editMerchantEmailModalCtrl',
          resolve: {
            current: function() {
              return $scope.merchant.details;
            },
          },
        });
        modalInstance.result.then(function(email) {
          $scope.editMerchantEmail(email);
        }, $.noop);
      };
      $scope.openChangeBankAccountDetails = function() {
        $scope.merchant.bank_account = {};
        var data = {
          route_name: 'merchant_fetch_bank_account',
          url_params: {
            '{id}': $scope.merchant.id,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request.success(function(data) {
          if (data.success) {
            $scope.merchant.bank_account = data.data;

            var modalInstance = $modal.open({
              templateUrl: 'changeBankAccountDetailsModalContent.html',
              controller: 'changeBankAccountDetailsModalCtrl',
              resolve: {
                current: function() {
                  return $scope.merchant.bank_account;
                },
              },
            });
            modalInstance.result.then(function(bankAccount) {
              $scope.changeBankAccountDetails(bankAccount);
            }, $.noop);
          } else {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };
      $scope.merchantUploadBatchFiles = function(files) {
        var fileUploaded = false;
        var fd = new FormData();
        fd.append('route_name', 'merchant_batches');
        fd.append(
          'url_params',
          JSON.stringify({
            '{id}': $scope.merchant.id,
          })
        );
        fd.append('body[type]', 'irctc');
        if (files.hasOwnProperty('refund') === true) {
          fd.append('file[data][refund]', files.refund);
          fileUploaded = true;
        }
        if (files.hasOwnProperty('settlement') === true) {
          fd.append('file[data][settlement]', files.settlement);
          fileUploaded = true;
        }

        if (fileUploaded === false) {
          $scope.alerts.addAlert('danger', 'Please upload atleast one file');
          return false;
        }

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          headers: {
            'Content-Type': undefined,
          },
          data: fd,
          transformRequest: angular.identity,
        });
        request.success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Uploaded Successfully ' + JSON.stringify(data.data),
              true
            );
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };
      $scope.openMerchantBatchUpload = function() {
        var modalInstance = $modal.open({
          templateUrl: 'merchantBatchUploadContent.html',
          controller: 'merchantBatchUploadCtrl',
        });
        modalInstance.result.then(function(files) {
          $scope.merchantUploadBatchFiles(files);
        }, $.noop);
      };
      $scope.openUploadScreenshot = function() {
        var currentId = $scope.merchant.id;
        $modal.open({
          templateUrl: 'uploadScreenshotModalContent.html',
          controller: 'uploadScreenshotModalCtrl',
          resolve: {
            current: function() {
              return currentId;
            },
          },
        });
      };
      $scope.openEditComment = function() {
        var modalInstance = $modal.open({
          templateUrl: 'editCommentModalContent.html',
          controller: 'editCommentModalCtrl',
          resolve: {
            current: function() {
              return $scope.merchant.details.merchant_details.comment;
            },
          },
        });
        modalInstance.result.then(function(merchant) {
          $scope.editComment(merchant);
        }, $.noop);
      };
      $scope.openAssignBanks = function() {
        var currentId = $scope.merchant.id;
        var modalInstance = $modal.open({
          templateUrl: 'assignBanksModalContent.html',
          controller: 'assignBanksModalCtrl',
          resolve: {
            current: function() {
              return currentId;
            },
          },
        });
        modalInstance.result.then(function(bankdata) {
          $scope.assignBanks(bankdata);
        }, $.noop);
      };

      $scope.openAssignMerchantHandle = function() {
        var modalInstance = $modal.open({
          templateUrl: 'assignMerchantHandle.html',
          controller: 'assignMerchantHandleCtrl',
          resolve: {
            handle: function() {
              return $scope.merchant.details.handle;
            },
          },
        });
        modalInstance.result.then(function(handle) {
          $scope.assignMerchantHandle(handle);
        }, $.noop);
      };

      $scope.openAddAdjustment = function() {
        var modalInstance = $modal.open({
          templateUrl: 'addAdjustmentModalContent.html',
          controller: 'addAdjustmentModalCtrl',
        });
        modalInstance.result.then(function(adjustment) {
          $scope.addAdjustment(adjustment);
        }, $.noop);
      };
      $scope.openAutofillForms = function() {
        $modal.open({
          templateUrl: 'openAutofillForms.html',
          controller: 'openAutofillForms',
          windowClass: 'modal-print',
          resolve: {
            current: function() {
              return $scope.merchant.details;
            },
          },
        });
      };

      // Credits
      $scope.openCredits = function() {
        var modalInstance = $modal.open({
          templateUrl: 'openCredits.html',
          controller: 'openCredits',
        });
        modalInstance.result.then(function(creditsData) {
          // Transform money from paise to rupee
          var creditsDataCloned = JSON.parse(JSON.stringify(creditsData));
          var mode = creditsDataCloned.mode;
          delete creditsDataCloned.mode;

          var data = {
            route_name: 'credits_create',
            url_params: {
              '{id}': $scope.merchant.id,
            },
            body: creditsDataCloned,
            mode: mode,
          };
          var request = $http({
            method: 'post',
            url: '/admin/generic',
            data: data,
          });
          request.success(function(data) {
            if (data.success) {
              if (utils.isWorkflow(data.data)) {
                $state.go('app.workflows.actions.detail', {
                  action_id: data.data.id,
                });
              } else {
                $scope.alerts.addAlert(
                  'success',
                  'Credits added successfully',
                  true
                );
              }
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          });
        });
      };

      // Open download report modal
      $scope.openDownloadReport = function() {
        var modalInstance = $modal.open({
          templateUrl: 'downloadReportModalContent.html',
          controller: 'downloadReportModalCtrl',
          resolve: {
            merchant: function() {
              return $scope.merchant.details;
            },
          },
        });

        modalInstance.result.then(function(reportOptions) {
          $scope.prepareReport(reportOptions);
        }, $.noop);
      };

      function getCreditsLog(mode) {
        var data = {
          route_name: 'credits_fetch_multiple',
          merchant_id: $scope.merchant.id,
          mode: mode,
        };
        admin.identity().then(function(adminData) {
          if (
            adminData.permissions.indexOf('view_merchant_credits_log') !== -1
          ) {
            var request = $http.get('/admin/generic', {
              params: data,
            });
            request.success(function(data) {
              if (data.success) {
                $scope.merchant.creditsLog = data.data;
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            });
          }
        });
      }
      $scope.getCreditsLog = getCreditsLog;

      $scope.deleteCredit = function(creditId, $index) {
        creditId = creditId.split('_')[1];

        var data = {
          route_name: 'credits_delete',
          url_params: {
            '{mid}': $scope.merchant.id,
            '{id}': creditId,
          },
          mode: $scope.merchant.creditsLogMode,
        };
        var request = $http.delete('/admin/generic', {
          params: data,
        });
        request.success(function(data) {
          if (data.success) {
            // Remove the object from the model
            $scope.merchant.creditsLog.items.splice($index, 1);
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      function sortTerminals() {
        // Doing this to avoid the whole exercise of custom sorting again. 'Two' is deleted. The other two are undeleted.
        var terminals = {
          undeleted_enabled: [],
          undeleted_disabled: [],
          deleted: [],
        };

        var undeleted = $scope.merchant.terminals.items.filter(function(x) {
          return x.deleted_at == null;
        });
        terminals.undeleted_enabled = undeleted.filter(function(x) {
          return x.enabled;
        });
        terminals.undeleted_disabled = undeleted.filter(function(x) {
          return !x.enabled;
        });
        terminals.deleted = $scope.merchant.terminals.items.filter(function(x) {
          return x.deleted_at !== null;
        });

        $scope.terminals = terminals;
      }

      // Create mapping for is vs admin details to be shown in table
      function createMapping(admins) {
        $scope.adminMap = {};

        admins.forEach(function(admin) {
          var adminObj = {
            id: admin.id,
            name: admin.name,
            role: admin.roles.length && admin.roles[0].name
              ? admin.roles[0].name
              : '--',
          };

          $scope.adminMap[admin.id] = adminObj; // create mapping id - name
        });
      }

      // Fetch list of admins
      var users = organization.fetchUsers();

      if (typeof users.then === 'function') {
        users.then(createMapping);
      } else {
        createMapping(users);
      }

      // Get offers of merchant to display in the list
      function getOffersOfMerchant() {
        var data = {
          route_name: 'admin_fetch_entity_multiple',
          url_params: {
            '{type}': 'offer',
          },
          mode: 'live',
          query_params: {
            merchant_id: $scope.merchant.id,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.merchantOffers = data.data.items;
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }

      // Create merchant offer from the modal form
      $scope.createMerchantOffer = function(offer, mode) {
        var request = $http({
          url: 'admin/generic',
          method: 'POST',
          data: {
            route_name: 'offer_create',
            content_type: 'application/json',
            mode: mode,
            merchant_id: $scope.merchant.id,
            body: offer,
          },
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Offer is successfully created',
                true
              );

              getOffersOfMerchant(); // Update offers list in merchant details when offer is created
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };

      function getGatewayRulesOfMerchant() {
        var data = {
          route_name: 'admin_fetch_entity_multiple',
          url_params: {
            '{type}': 'gateway_rule',
          },
          mode: 'live',
          query_params: {
            merchant_id: $scope.merchant.id,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.gatewayRules = data.data.items;
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }
      function fetchTerminalRequest(request_mode) {
        var request = $http.get('/admin/generic', {
          params: {
            route_name: 'merchant_get_terminals',
            url_params: {
              '{id}': $scope.merchant.id,
            },
            mode: request_mode,
          },
        });

        return request;
      }
      function addEntityMode(items, mode) {
        items = items.map(function(obj) {
          obj.entity_mode = mode;
          return obj;
        });
      }
      function fetchTerminals() {
        var items = [];
        var count = 0;
        // test mode
        var liveTerminalRequest = fetchTerminalRequest('live');
        liveTerminalRequest
          .success(function(data) {
            if (data.success) {
              addEntityMode(data.data.items, 'live');
              $scope.merchant.terminals.items = $scope.merchant.terminals.items.concat(
                data.data.items
              );
              sortTerminals();
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });

        var testTerminalRequest = fetchTerminalRequest('test');
        testTerminalRequest
          .success(function(data) {
            if (data.success) {
              addEntityMode(data.data.items, 'test');
              $scope.merchant.terminals.items = $scope.merchant.terminals.items.concat(
                data.data.items
              );
              sortTerminals();
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }

      function fetchPricingPlans() {
        var data = {
          route_name: 'merchant_get_pricing',
          url_params: {
            '{id}': $scope.merchant.id,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.merchant.pricing_plan = data.data;
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }

      function fetchScheduleTasks() {
        var data = {
          route_name: 'admin_fetch_entity_multiple',
          url_params: {
            '{type}': 'schedule_task',
          },
          query_params: {
            merchant_id: $scope.merchant.id,
          },
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });

        request
          .success(function(data) {
            if (data.success) {
              $scope.merchant.schedule_tasks = data.data;

              $scope.hasSettlementSchedule = false;
              if ($scope.merchant.schedule_tasks) {
                for (var key in $scope.merchant.schedule_tasks.items) {
                  if (
                    $scope.merchant.schedule_tasks.items[key]['type'] ===
                    'settlement'
                  ) {
                    $scope.hasSettlementSchedule = true;
                    break;
                  }
                }
              }
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }

      function generateMerchant() {
        var data = {
          route_name: 'merchant_details_fetch',
          account_id: $scope.merchant.id,
          merchant_id: $scope.merchant.id,
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            $scope.alerts.resetAlerts(true);

            if (data.success) {
              $scope.merchant.details = data.data;
              $scope.merchant.terminals = {
                items: [],
                count: 0,
              };
              fetchTerminals();
              admin.identity().then(function(adminData) {
                if (
                  adminData.permissions.indexOf('view_merchant_pricing') !== -1
                ) {
                  fetchPricingPlans();
                }
              });
              fetchScheduleTasks();

              $scope.scheduleKeys = [
                'schedule_name',
                'type',
                'method',
                'schedule_id',
                'next_run_at',
              ];
              $scope.merchant.id = $scope.merchant.details.id;
              $scope.merchant.details.activation_progress =
                $scope.merchant.details.merchant_details.activation_progress;
              $scope.merchant.details.activated_dashboard =
                $scope.merchant.details.merchant_details.activated;
              $scope.merchant.details.locked =
                $scope.merchant.details.merchant_details.locked;
              $scope.merchant.details.submitted =
                $scope.merchant.details.merchant_details.submitted;
              $scope.merchant.details.submitted_at =
                $scope.merchant.details.merchant_details.submitted_at;
              $scope.referer = getReferer($scope.merchant.details.tags);
              $scope.marketplace = $scope.merchant.details.parent_id;
              $scope.merchant.details.gstin =
                $scope.merchant.details.merchant_details.gstin;

              var merchantAdmins = $scope.merchant.details.admins || [];
              $scope.selected_admins = [];

              // Re-populated array with selected admin ids
              merchantAdmins.map(function(admin) {
                $scope.selected_admins.push(admin.id);
              });

              fetchBalance();
              getMerchantFeatures();

              $scope.merchant.creditsLogMode = 'live';
              getCreditsLog($scope.merchant.creditsLogMode);

              if ($scope.merchant.details.confirmed === false) {
                $scope.unconfirmed = true;
                $scope.alerts.addAlert('danger', 'Merchant not confirmed');
              }
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
                if (data.errors[0] === 'Merchant not confirmed') {
                  $scope.unconfirmed = true;
                }
              });
            }
          })
          .error(function() {
            $scope.alerts.resetAlerts(true);
            $scope.alerts.addAlert('danger', null);
          });
      }

      $scope.getModeBasedFeatures = function(mode) {
        var data = {
          route_name: 'feature_get_multiple',
          url_params: {
            '{entityId}': $scope.merchant.id,
          },
          mode: mode,
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              // Full list of features which can be assigned to merchant
              $scope.merchant.details.allowedFeatures =
                $scope.merchant.details.allowedFeatures || {};
              $scope.merchant.details.allowedFeatures[mode] =
                data.data.all_features;
              // List of features currently assigned to merchant
              $scope.merchant.details.features =
                $scope.merchant.details.features || {};
              $scope.merchant.details.features[mode] = getFeatureNames(
                data.data.assigned_features
              );

              Object.keys(data.data.assigned_features).forEach(function(key) {
                if (data.data.assigned_features[key].name === 'irctc_report') {
                  $scope.showMerchantBatchUpload = true;
                }
              });
            } else {
              $scope.alerts.resetAlerts(true);
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null);
          });
      };

      function getMerchantFeatures() {
        admin.identity().then(function(adminData) {
          if (adminData.permissions.indexOf('view_merchant_features') !== -1) {
            $scope.getModeBasedFeatures('test');
            $scope.getModeBasedFeatures('live');
          }
        });
      }

      /**
     * Plucks the id and name from array of feature objects
     * @param  {array} features [Array of feature objects]
     * @return {array}
     */
      function getFeatureNames(features) {
        var featureNames = features.map(function(feature) {
          return {
            id: feature.id,
            name: feature.name,
          };
        });
        return featureNames;
      }

      $scope.deleteFeature = function(featureName, featureMode) {
        var request = $http.delete('/admin/generic', {
          params: {
            route_name: 'feature_delete',
            url_params: {
              '{entityId}': $scope.merchant.id,
              '{featureName}': featureName,
            },
            mode: featureMode,
          },
        });
        request.success(function(data) {
          if (data.success) {
            if (utils.isWorkflow(data.data)) {
              $state.go('app.workflows.actions.detail', {
                action_id: data.data.id,
              });
            }
            var features = $scope.merchant.details.features[featureMode];
            $scope.merchant.details.features[
              featureMode
            ] = features.filter(function(item) {
              return item.id !== data.data.id;
            });
          } else {
            $scope.alerts.resetAlerts(true);
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      function fetchBalance() {
        $scope.merchant.balance = {};
        $scope.merchant.credits = {};
        $scope.merchant.fee_credits = {};

        admin.identity().then(function(adminData) {
          if (adminData.permissions.indexOf('view_merchant_balance') !== -1) {
            // live mode
            var request = $http.get('/admin/generic', {
              params: {
                route_name: 'balance_fetch',
                merchant_id: $scope.merchant.id,
                mode: 'live',
              },
            });
            request
              .success(function(data) {
                if (data.success) {
                  $scope.merchant.balance.live = data.data.balance;
                  $scope.merchant.credits.live = data.data.credits;
                  $scope.merchant.fee_credits.live = data.data.fee_credits;
                } else {
                  $scope.alerts.resetAlerts();
                  angular.forEach(data.errors, function(value) {
                    $scope.alerts.addAlert('danger', value);
                  });
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger', null, true);
              });

            // test mode
            var request = $http.get('/admin/generic', {
              params: {
                route_name: 'balance_fetch',
                merchant_id: $scope.merchant.id,
                mode: 'test',
              },
            });
            request.success(function(data) {
              if (data.success) {
                $scope.merchant.balance.test = data.data.balance;
                $scope.merchant.credits.test = data.data.credits;
                $scope.merchant.fee_credits.test = data.data.fee_credits;
              }
            });
          }
        });
      }
    },
  ])
  .controller('assignPricingModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      $scope.loading = true;
      $scope.pricing_plans = {};
      $scope.pricing_plan_id = current;
      var params = {
        route_name: 'pricing_get_merchant_plans',
      };
      var request = $http.get('/admin/generic', {
        params: params,
      });
      request.success(function(data) {
        if (data.success) {
          for (var key in data.data) {
            var value = data.data[key];
            $scope.pricing_plans[value.plan_id] = value.plan_name;
          }
          $scope.loading = false;
        }
      });

      $scope.pricingPlansLength = function() {
        return Object.keys($scope.pricing_plans).length;
      };

      $scope.ok = function(pricing_plan_id) {
        var pricingPlanName = $scope.pricing_plans[pricing_plan_id];
        $modalInstance.close({ id: pricing_plan_id, name: pricingPlanName });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editMerchantMethodsCtrl', [
    '$scope',
    '$modalInstance',
    'methods',
    function($scope, $modalInstance, methods) {
      // Makes sure we have all methods listed
      // This lets us display methods that are not returned
      // by the API as false
      var forcedMethods = [
        'aeps',
        'bank_transfer',
        'mobikwik',
        'payzapp',
        'payumoney',
        'olamoney',
        'mpesa',
        'upi',
        'airtelmoney',
        'freecharge',
        'emi',
        'amex',
        'netbanking',
        'debit_card',
        'credit_card',
        'jiomoney',
        'openwallet',
        'sbibuddy',
      ];
      $scope.methods = {};

      forcedMethods.map(function(method) {
        // Assign a default of false and override if we have it
        $scope.methods[method] = false;

        if (methods.hasOwnProperty(method)) {
          $scope.methods[method] = methods[method];
        }
      });

      // This is the unedited methods
      var defaultMethods = jQuery.extend({}, $scope.methods);

      $scope.changedMethods = function() {
        var methods = $scope.methods;
        for (var method in methods) {
          if (methods[method] === defaultMethods[method]) {
            delete methods[method];
          }
        }
        return methods;
      };

      $scope.ok = function() {
        // We only want to send methods that were edited
        // from their original
        var methodsDiff = $scope.changedMethods();
        $modalInstance.close(methodsDiff);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('assignTerminalModalCtrl', [
    '$scope',
    '$modalInstance',
    'alertsFactory',
    'assignTerminal',
    function($scope, $modalInstance, alertsFactory, assignTerminal) {
      $scope.alerts = alertsFactory.getHandler();
      $scope.terminal = { gateway: 'hdfc', mode: 'live', card: 1 };
      $scope.onFileSelect = function($files) {
        var file = $files[0];
        if (
          $scope.terminal.gateway === 'first_data' &&
          file.type !== 'application/x-pkcs12'
        ) {
          $scope.alerts.addAlert('danger', 'Invalid certificate file', true);
          return;
        }
        $scope.terminal.gateway_client_certificate = file;
      };
      $scope.ok = function() {
        assignTerminal($scope.terminal, $modalInstance, $scope.alerts);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss();
      };
    },
  ])
  .controller('assignBanksModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      $scope.loading = true;
      $scope.banks = [];
      $scope.bankdata = {};
      $scope.merchant_id = current;
      $scope.selectAllChange = function(value) {
        angular.forEach($scope.bankdata, function(i, e) {
          $scope.bankdata[e] = value;
        });
      };
      var data = {
        route_name: 'merchant_get_banks',
        url_params: {
          '{id}': current,
        },
      };
      var request = $http.get('/admin/generic', {
        params: data,
      });
      request.success(function(data) {
        if (data.success) {
          $scope.banks = data.data;
          angular.forEach($scope.banks.enabled, function(key, value) {
            $scope.bankdata[value] = true;
          });
          angular.forEach($scope.banks.disabled, function(key, value) {
            $scope.bankdata[value] = false;
          });
          $scope.loading = false;
        }
      });
      $scope.ok = function(bankdata) {
        $modalInstance.close(bankdata);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('assignMerchantHandleCtrl', [
    '$scope',
    '$modalInstance',
    'handle',
    function($scope, $modalInstance, handle) {
      $scope.handle = handle;
      $scope.ok = function(handle) {
        $modalInstance.close(handle);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('addAdjustmentModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.ok = function(adjustment) {
        $modalInstance.close(adjustment);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editMerchantModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    'riskMap',
    'groups',
    'organization',
    'selected_groups',
    'selected_admins',
    'permissions',
    function(
      $scope,
      $modalInstance,
      current,
      riskMap,
      groups,
      organization,
      selected_groups,
      selected_admins,
      permissions
    ) {
      $scope.riskMap = riskMap;
      $scope.groups = groups;
      $scope.selected_groups = selected_groups;
      $scope.selected_admins = selected_admins;
      $scope.permissions = permissions;

      $scope.adminMap = {};

      function createMapping(users) {
        $scope.admins = [];
        users.forEach(function(admin) {
          var adminObj = {
            id: admin.id,
            name: admin.name,
            email: admin.email,
          };

          $scope.admins.push(adminObj); // create admin users object
          $scope.adminMap[admin.id] = admin.name; // create mapping id - name
        });
      }

      // Fetch list of admins
      $scope.users = organization.fetchUsers();

      if (typeof $scope.users.then === 'function') {
        $scope.users.then(createMapping);
      } else {
        createMapping($scope.users);
      }

      // Remove role which is already selected
      $scope.removeUser = function(adminId) {
        var index = $scope.selected_admins.indexOf(adminId);
        if (index > -1) {
          $scope.selected_admins.splice(index, 1);
        }
      };

      // Remove user from the list if already added in selected_admins
      $scope.filterAdmins = function(admin) {
        if ($scope.selected_admins.indexOf(admin.id) > -1) {
          return false;
        }
        return true;
      };

      // Attach event listener for selection on custom select tag
      // Called from directive - roleSelect
      $scope.initRoleSelector = function(element) {
        element.on('select2:select', function(e) {
          var elem = e.params.data.element;
          var adminId = elem.value;

          // Add user in selected_admins if not already added
          if ($scope.selected_admins.indexOf(adminId) === -1) {
            $scope.selected_admins.push(adminId);
          }

          element.val(null).trigger('change');
        });
      };

      if (!current.website) {
        current.website = current.merchant_details.business_website;
      }
      if (!current.billing_label) {
        current.billing_label = current.merchant_details.business_dba;
      }
      if (!current.transaction_report_email) {
        current.transaction_report_email =
          current.merchant_details.transaction_report_email;
      }
      angular.forEach(current.groups, function(group) {
        $scope.selected_groups[group.id] = true;
      });

      $scope.current = current;
      $scope.ok = function(merchant) {
        // We convert it back from INR to paise.
        merchant.max_payment_amount = merchant.max_payment_amount * 100;
        $modalInstance.close(merchant);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('createMerchantOfferModalCtrl', [
    '$scope',
    'dateFactory',
    'utilMapping',
    '$modalInstance',
    function($scope, dateFactory, utilMapping, $modalInstance) {
      $scope.offer = {};
      $scope.mode = { value: 'live' }; //set default mode as test

      // Payment network map to have different dropdown values depending upon payment method
      $scope.updatePaymentNetworkMap = function() {
        $scope.paymentNetworkMap = {};
        if ($scope.offer.payment_method === 'wallet') {
          $scope.paymentNetworkMap = utilMapping.getMap('walletMap');
        }
      };

      $scope.walletMap = utilMapping.getMap('walletMap');
      $scope.cardNetworkMap = utilMapping.getMap('networkMap');

      $scope.date = dateFactory.getHandler($scope);
      $scope.date.dateOptions['showWeeks'] = false;
      $scope.date.dateOptions['minDate'] = moment(); // Avoid selection of date before today

      var today = new Date();
      $scope.currentDate = today.setHours(0, 0, 0, 0);
      $scope.offer_time = {
        starts: today,
        ends: today,
      };

      function cleanFields() {
        var offer = Object.assign({}, $scope.offer);
        // 1. iins is for only card and emi.
        if (
          ['netbanking', 'wallet', 'upi'].indexOf(offer.payment_method) !== -1
        ) {
          delete offer['iins'];
        } else if (offer['iins']) {
          offer['iins'] = offer['iins'].split(','); // Convert command separate values to array
        }

        // 2. Max payment count to be sent only when payment method = card
        if (offer['payment_method'] !== 'card') {
          delete offer['max_payment_count'];
        }

        // 3. Payment method is not required for upi
        if (offer['payment_method'] === 'upi') {
          delete offer['payment_network'];
        }

        // 4. Convert to array
        if (offer['linked_offer_ids']) {
          offer['linked_offer_ids'] = offer['linked_offer_ids'].split(',');
        }

        // 5. Percent rate has limit 0-10000 (view takes from 0-100)
        offer['percent_rate'] = offer['percent_rate'] * 100;

        // 6. Form the start and end time in unix timestamp form date and time taken separately for both start and end date
        var startTime = new Date($scope.offer_time.starts);
        var endTime = new Date($scope.offer_time.ends);

        if (offer.starts_at) {
          var offsetStart =
            startTime.getHours() * 60 * 60 + startTime.getMinutes() * 60;

          offer.starts_at =
            new Date(offer.starts_at).getTime() + offsetStart * 1000;

          offer.starts_at = offer.starts_at / 1000;
        }
        if (offer.ends_at) {
          var offsetEnd =
            endTime.getHours() * 60 * 60 + endTime.getMinutes() * 60;

          offer.ends_at = new Date(offer.ends_at).getTime() + offsetEnd * 1000;

          offer.ends_at = offer.ends_at / 1000;
        }

        // Remove keys with null/empty value
        Object.keys(offer).forEach(function(key) {
          if (!offer[key]) {
            delete offer[key];
          }
        });

        return offer;
      }

      $scope.ok = function() {
        $modalInstance.close({
          offer: cleanFields(),
          mode: $scope.mode.value,
        });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editMerchantEmailModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      $scope.current = current;
      $scope.ok = function(email) {
        $modalInstance.close(email);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editMerchantNameModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      $scope.current = current;
      $scope.ok = function(name) {
        $modalInstance.close(name);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('changeBankAccountDetailsModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      $scope.current = current;

      $scope.ok = function(merchant_details) {
        $modalInstance.close(merchant_details);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editCommentModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      $scope.current = current;
      $scope.ok = function(merchant) {
        $modalInstance.close(merchant);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('tagModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      // We need to keep it to a csv field
      $scope.tags = current.join();
      $scope.ok = function(tags) {
        $modalInstance.close(tags);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('referralModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      // We need to keep it to a csv field
      $scope.referral = current;
      $scope.ok = function(referral) {
        $modalInstance.close(referral);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('featureModalCtrl', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      $scope.features = current.shift();
      $scope.assigned_features = {};
      var assignedFeatures = current.shift();
      $scope.assigned_features.test = assignedFeatures.test.join(',');
      $scope.assigned_features.live = assignedFeatures.live.join(',');
      $scope.selectedFeatures = [];
      $scope.featuremode = 'test';
      $scope.ok = function(features, mode, shouldSync) {
        $modalInstance.close({
          features: features,
          mode: mode,
          shouldSync: shouldSync,
        });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('merchantBatchUploadCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.files = {};
      $scope.onFileSelect = function($files, fileName) {
        var file = $files[0];
        $scope.files[fileName] = file;
      };
      $scope.ok = function() {
        $modalInstance.close($scope.files);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('uploadScreenshotModalCtrl', [
    '$scope',
    '$modalInstance',
    '$upload',
    'current',
    'alertsFactory',
    function($scope, $modalInstance, $upload, current, alertsFactory) {
      $scope.files = {};
      $scope.merchantId = current;
      $scope.alerts = alertsFactory.getHandler();
      $scope.onFileSelect = function($files, fieldname) {
        var file = $files[0];
        if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
          $scope.alerts.addAlert(
            'danger',
            'Invalid filetype. Only jpg and png files are allowed.',
            true
          );
          return;
        }
        $scope.alerts.addAlert('info', 'Uploading...', true);
        var request = $upload.upload({
          url: '/admin/merchant/' + $scope.merchantId + '/screenshot',
          method: 'post',
          file: file,
          alias: fieldname,
          name: fieldname,
          fileFormDataName: fieldname,
          formDataAppender: function(fd, key, val) {
            if (angular.isArray(val)) {
              angular.forEach(val, function(v) {
                fd.append(key, v);
              });
            } else {
              fd.append(key, val);
            }
          },
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'File Uploaded Successfully',
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', 'File upload failed.', true);
          });
      };
      $scope.ok = function(files) {
        $modalInstance.close(files);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('openAutofillForms', [
    '$scope',
    '$modalInstance',
    'current',
    function($scope, $modalInstance, current) {
      var merchant_details = current.merchant_details;
      var html = '';
      var bankDocument = '';
      $scope.ok = function() {
        if (bankDocument === 'hdfc-excel') {
          var id = merchant_details.id;
          window.location = '/admin/merchant/' + current.id + '/hdfc_excel';
        }
        if (html) {
          var w = window.open();
          w.document.body.innerHTML = html;
        }
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
      $scope.select = function(doc) {
        $('.modal-ok').attr('disabled', 'disabled');
        bankDocument = doc;
        if (bankDocument === 'hdfc-excel') {
          $('.modal-ok').removeAttr('disabled');
          return;
        }
        $.ajax({
          url: '/admin-forms/' + bankDocument + '.html',
          complete: function() {
            $('.modal-ok').removeAttr('disabled');
          },
          success: function(resp) {
            doT.templateSettings.strip = false;
            var template = doT.template(resp);
            var reg_addr = merchant_details.business_registered_address;
            if (reg_addr) reg_addr += ', ';
            if (merchant_details.business_registered_city) {
              reg_addr += merchant_details.business_registered_city;
              if (merchant_details.business_registered_pin)
                reg_addr += '-' + merchant_details.business_registered_pin;
              reg_addr += ', ';
            }
            reg_addr += merchant_details.business_registered_state;
            var ops_addr = merchant_details.business_operation_address;
            if (ops_addr) ops_addr += ', ';
            if (merchant_details.business_operation_city) {
              ops_addr += merchant_details.business_operation_city;
              if (merchant_details.business_operation_pin)
                ops_addr += '-' + merchant_details.business_operation_pin;
              ops_addr += ', ';
            }
            ops_addr += merchant_details.business_operation_state;
            var now = new Date();
            var nowdate = ('0' + now.getDate()).slice(-2);
            var nowmonth = ('0' + (1 + now.getMonth())).slice(-2);
            var nowyear = now.getYear() + 1900;
            html = template({
              billing_label: current.billing_label || '',
              date: nowdate + '/' + nowmonth + '/' + nowyear,
              reqdate: nowdate + nowmonth + nowyear,
              reqby: 'Harshil Mathur',
              reqsign: '',
              contract_merchant: '',
              contract_corporate: '',
              contract_business: '',
              contract_software: '',
              contract_govt: '',
              contract_other: 'Y',
              contract_specify: merchant_details.business_model || '',
              mercreg_company: merchant_details.business_name || '',
              mercreg_contact: merchant_details.contact_name || '',
              mercreg_tel_business: merchant_details.contact_mobile || '',
              mercreg_tel_after: merchant_details.contact_mobile || '',
              mercreg_fax: '',
              mercreg_email: merchant_details.contact_email || '',
              mercreg_addr: removeLineBreaks(reg_addr || ''),
              mercreg_country: 'India',
              mercreg_tz: 'GMT + 5:30 (IST)',
              mercop_company: merchant_details.business_name || '',
              mercop_contact: merchant_details.contact_name || '',
              mercop_tel_business: merchant_details.contact_mobile || '',
              mercop_tel_after: merchant_details.contact_mobile || '',
              mercop_fax: '',
              mercop_email: merchant_details.contact_email || '',
              mercop_addr: removeLineBreaks(ops_addr || ''),
              cpv_head: '',
              cpv_op: '',
              merctech_contact: 'Razorpay Software Private Limited',
              merctech_pos: 'Director',
              merctech_tel_business: '+91-8003393912',
              merctech_tel_after: '+91-8003393912',
              merctech_fax: '',
              merctech_email: 'harshil@razorpay.com',
              merctech_addr:
                '35, Vishnupuri, Opp. Malviya Nagar P.O., Jagatpura Road, Jaipur - 302017, Rajasthan',
              merctech_web_addr: current.website || '',
              merctech_return_url: 'https://api.razorpay.com',
              mercsetup_auth: 'Y',
              mercsetup_purc: '',
              mercsetup_catcode: current.category || '',
              mercsetup_3: '',
              mercsetup_6: '',
              mercsetup_9: '',
              mercsetup_12: '',
              mercsetup_master: 'Y',
              mercsetup_visa: 'Y',
              mercsetup_maestro: 'Y',
              mercsetup_dmid: (current.international && 'Y') || '',
              mercsetup_smid: (!current.international && 'Y') || '',
              techpro_company: '',
              techpro_contact: '',
              techpro_pos: '',
              techpro_tel_business: '',
              techpro_tel_after: '',
              techpro_fax: '',
              techpro_email: '',
              techpro_addr: '',
              paycli_merc: '',
              paycli_third: 'Y',
              paycli_hosting: 'Amazon Web Services',
              paycli_tel: '',
              paycli_win: '',
              paycli_winver: '',
              paycli_unix: '',
              paycli_unixver: '',
              paycli_linux: 'Y',
              paycli_linuxver: '14.04',
              paycli_other: '',
              paycli_specify: '',
              payapp_custbool: '',
              payapp_cust: '',
              payapp_thirdbool: '',
              payapp_third: '',
              payapp_otherbool: 'Y',
              payapp_specify: 'Self developed by Razorpay',
              payapp_langasp: '',
              payapp_langaspx: '',
              payapp_langjsock: '',
              payapp_langjava: '',
              payapp_langperl: '',
              payapp_langoth: '',
              payapp_langspecify: '',
              payapp_sslbool: 'Y',
              payapp_4card: '',
              payapp_6card: '',
              payapp_dndcard: '',
              payapp_secyes: 'Y',
              payapp_secno: '',
              payapp_uid: 'Razorpay',
              payapp_vbvyes: 'Y',
              payapp_vbvno: '',
              payapp_mscyes: 'Y',
              payapp_mscno: '',
            });
          },
        });
      };
    },
  ])
  .controller('openCredits', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.ok = function(credits) {
        $modalInstance.close(credits);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('downloadReportModalCtrl', [
    '$scope',
    '$modalInstance',
    'merchant',
    function($scope, $modalInstance, merchant) {
      $scope.merchant = merchant;

      $scope.reportForm = {
        entity: 'payment',
        type: 'monthly',
        year: 2017,
      };

      // return no. of days in a month
      function numberOfDays(month, year) {
        return moment(year + ' ' + month, 'YYYY M').daysInMonth();
      }

      // Delete the dependent fields in the form
      $scope.checkValue = function() {
        // delete the key
        if ($scope.reportForm.entity === 'invoice') {
          delete $scope.reportForm.type;
        }
      };

      // returns array of objects with 1: Jan, 2: Feb kind of mapping.
      $scope.monthFields = moment.months().map(function(name, index) {
        return { value: index + 1, name: name };
      });

      // Get array from range of Numbers (default step is 1). Used in getting range from 1 to total number of days in month
      $scope.range = function(min, max, step) {
        step = step || 1;
        var input = [];
        for (var i = min; i <= max; i += step) {
          input.push(i);
        }
        return input;
      };

      // Update date as per changes in type, month, year
      $scope.updateDates = function(month, year) {
        $scope.daysInSelectedMonth = numberOfDays(month, year); // total days in that month-year

        // Change the date if exceeding
        if ($scope.daysInSelectedMonth < $scope.reportForm.day) {
          $scope.reportForm.day = $scope.daysInSelectedMonth;
        }

        // Remove the date key if duration is no longer 'daily'
        if ($scope.reportForm.type === 'monthly') {
          delete $scope.reportForm.day;
        }
      };

      $scope.ok = function() {
        $modalInstance.close($scope.reportForm);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('assignScheduleModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      $scope.loading = true;
      $scope.schedule_list = {};

      $scope.type = current.type;
      $scope.schedule_id = current.schedule;
      $scope.method = current.method;

      var data = {
        route_name: 'setl_fetch_schedule',
      };
      var request = $http.get('/admin/generic', {
        params: data,
      });
      request
        .success(function(data) {
          $scope.loading = false;

          if (data.success) {
            angular.forEach(data.data.items, function(value) {
              $scope.schedule_list[value.id] = value.name;
            });

            $scope.type_list = { Settlement: 'settlement' };
            $scope.methods = [
              null,
              'card',
              'netbanking',
              'emi',
              'wallet',
              'upi',
              'bank_transfer',
            ];
          } else {
            var errors = [];
            angular.forEach(data.errors, function(value) {
              errors.push(value);
            });
            $scope.scheduleFetchError = errors.join(', ');
          }
        })
        .error(function() {
          $scope.loading = false;
          $scope.scheduleFetchError = 'Server Error';
        });

      $scope.scheduleListLength = function() {
        return Object.keys($scope.schedule_list).length;
      };

      $scope.ok = function(type, schedule_id, method) {
        $modalInstance.close({
          type: type,
          schedule_id: schedule_id,
          method: method,
        });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);

function removeLineBreaks(str) {
  return str.replace(/[\n|\r]/g, ' ');
}
