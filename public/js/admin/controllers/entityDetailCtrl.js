'use strict';

//Single Entity Details controller
app
  .controller('EntityDetailCtrl', [
    '$scope',
    '$http',
    '$stateParams',
    'alertsFactory',
    '$modal',
    'admin',
    'statusClass',
    'isStatusKey',
    'getState',
    'getType',
    'displayClass',
    'displayValue',
    '$state',
    function(
      $scope,
      $http,
      $stateParams,
      alertsFactory,
      $modal,
      admin,
      statusClass,
      isStatusKey,
      getState,
      getType,
      displayClass,
      displayValue,
      $state
    ) {
      //Intialise alerts and scope functions
      $scope.getStatusClass = statusClass;
      $scope.getState = getState;
      $scope.getType = getType;
      $scope.displayClass = displayClass;
      $scope.displayValue = displayValue;
      $scope.isStatusKey = isStatusKey;
      $scope.alerts = alertsFactory.getHandler();
      $scope.mode = $stateParams.mode;
      $scope.entity = { id: $stateParams.id };
      $scope.loadType = $stateParams.type;
      $scope.hasRetryRefund = false;
      $scope.isRetryRefundProcessing = false;

      var adminData = admin.identity().then(function(data) {
        $scope.admin = data;
      });

      $scope.generate = function(entityType) {
        adminData.then(function() {
          fetchEntity(entityType);
        });
      };

      function hasRetryRefund(entity) {
        return (
          $scope.admin.permissions.indexOf('retry_refund_failed') !== -1 &&
          $scope.loadType === 'refund' &&
          entity.status === 'failed'
        );
      }

      function fetchEntity(entityType) {
        var routeName = 'admin_fetch_entity_by_id';
        if (entityType === 'terminal') {
          routeName = 'admin_fetch_terminal_by_id';
        }
        var data = {
          route_name: routeName,
          url_params: {
            '{type}': entityType,
            '{id}': $scope.entity.id,
          },
          mode: $scope.mode,
        };
        var request = $http.get('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            $scope.alerts.resetAlerts();
            if (data.success) {
              $scope.entity = data.data;

              // api sends data in range 0-10000. Changing it into 0-100
              if (entityType === 'offer') {
                $scope.entity['percent_rate'] =
                  $scope.entity['percent_rate'] / 100;

                $scope.entity['iins'] = $scope.entity['iins']
                  ? $scope.entity['iins'].join(',')
                  : null;
              }

              $scope.hasRetryRefund = hasRetryRefund($scope.entity);
            } else {
              angular.forEach(data.errors, function(error) {
                $scope.alerts.addAlert('danger', error);
              });
            }
          })
          .error(function(res) {
            $scope.alerts.addAlert('danger', res ? res : null, true);
          });
      }

      // Used for download file action
      $scope.downloadFile = function() {
        var windowRef = window.open('', '_blank');
        var data = {
          route_name: 'admin_get_file',
          url_params: {
            '{fileId}': $scope.entity.id,
          },
        };

        var request = $http.get('/admin/generic', {
          params: data,
        });

        request.success(function(data) {
          if (data.success) {
            windowRef.location.href = data.data.url;
          } else {
            windowRef.close();
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      // Dispute specific actions
      $scope.dispute = {
        edit: function(data) {
          var params = {
            route_name: 'dispute_edit',
            url_params: {
              '{id}': $scope.entity.id,
            },
            mode: $scope.mode,
            body: data,
          };

          var request = $http({
            method: 'patch',
            url: '/admin/generic',
            data: params,
          });
          request
            .success(function(data) {
              if (data.success) {
                if (data.data.workflow_id) {
                  $state.go('app.workflows.actions.detail', {
                    action_id: data.data.id,
                  });
                } else {
                  window.location.reload();
                }
              } else {
                angular.forEach(data.errors, function(value) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        },
      };

      // Offer Specific actions
      $scope.offer = {
        edit: function(offer) {
          var body = offer;

          var successMsg = 'Offer is successfully Updated';
          if (body.hasOwnProperty('active')) {
            successMsg = 'Offer is successfully Deactivated';
          }

          var request = $http({
            url: '/admin/generic',
            method: 'PATCH',
            params: {
              route_name: 'offer_update',
              mode: $scope.mode,
              url_params: {
                '{id}': $scope.entity.id,
              },
            },
            data: {
              merchant_id: $scope.entity.merchant_id,
              body: body,
            },
          });

          request
            .success(function(data) {
              if (data.success) {
                $scope.alerts.addAlert('success', successMsg, true);

                // Update UI if request for deactivation is successful
                if (offer.active === 0) {
                  $scope.entity.active = false;
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
        },
      };

      // Terminal Specific actions
      $scope.terminal = {
        delete: function(id) {
          var data = {
            route_name: 'terminal_delete',
            url_params: {
              '{id}': id,
            },
            mode: $scope.mode,
          };
          var request = $http.delete('/admin/generic', {
            params: data,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('Terminal deleted');
                $state.go('app.entities', {
                  mode: $scope.mode,
                  type: 'terminal',
                });
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while deleting the terminal');
            });
        },
        edit: function(id, data) {
          delete data.id;
          // Lets remove all the empty variables
          for (var i in data) {
            if (data[i] === '' || data[i] === null) {
              delete data[i];
            }
          }
          var terminalData = {
            route_name: 'terminal_edit',
            url_params: {
              '{id}': id,
            },
            mode: $scope.mode,
            body: data,
          };
          var request = $http({
            method: 'put',
            url: '/admin/generic',
            data: terminalData,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('Terminal edit successfully');
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while editing the terminal');
            });
        },
        disable: function(id) {
          var data = { toggle: 0 };
          var TerminalData = {
            route_name: 'terminal_toggle',
            url_params: {
              '{id}': id,
            },
            mode: $scope.mode,
            body: data,
          };
          var request = $http({
            method: 'put',
            url: '/admin/generic',
            data: TerminalData,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('Terminal disabled');
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while disabling the terminal');
            });
        },
        enable: function(id) {
          var data = { toggle: 1 };
          var TerminalData = {
            route_name: 'terminal_toggle',
            url_params: {
              '{id}': id,
            },
            mode: $scope.mode,
            body: data,
          };
          var request = $http({
            method: 'put',
            url: '/admin/generic',
            data: TerminalData,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('Terminal enabled');
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while enabling the terminal');
            });
        },
        addSubMerchant: function(id, merchant_id) {
          var data = {
            route_name: 'terminal_add_merchant',
            url_params: {
              '{id}': id,
              '{mid}': merchant_id,
            },
            mode: $scope.mode,
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
                  'Sub Merchant added successfully'
                );

                $scope.entity.sub_merchants.unshift(merchant_id);

                // Page refresh
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              $scope.alerts.addAlert(
                'danger',
                'There was an error while adding the merchant to the terminal'
              );
            });
        },
        changePrimaryMerchant: function(terminal_id, merchant_id) {
          var data = {
            route_name: 'terminal_reassign_merchant',
            url_params: {
              '{id}': terminal_id,
            },
            body: {
              merchant_id: merchant_id,
            },
            mode: $scope.mode,
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
                  'Primary merchant changed successfully'
                );

                // Page refresh
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              $scope.alerts.addAlert(
                'danger',
                'There was an issue while changing the primary merchant'
              );
            });
        },
      };

      // IIN Specific actions
      $scope.iin = {
        edit: function(iin) {
          var iinId = iin.iin;

          iin = {
            category: iin.category,
            country: iin.country,
            emi: iin.emi ? 1 : 0,
            issuer_name: iin.issuer_name,
            issuer: iin.issuer,
            trivia: iin.trivia,
            network: iin.network,
            type: iin.type,
          };
          // Lets remove all the empty variables
          for (var i in iin) {
            if (iin[i] === '' || iin[i] === null) {
              delete iin[i];
            }
          }
          var data = {
            route_name: 'iin_edit',
            url_params: {
              '{id}': iinId,
            },
            body: iin,
          };
          var request = $http({
            method: 'put',
            url: '/admin/generic',
            data: data,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('IIN edit successfully');
                window.location.reload();
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while editing the terminal');
            });
        },
      };

      // EMI Specific actions
      $scope.emi = {
        delete: function(id) {
          // EMI plans are also modeless
          var data = {
            route_name: 'emi_plan_delete',
            url_params: {
              '{id}': id,
            },
          };
          var request = $http.delete('/admin/generic', {
            params: data,
          });
          request
            .success(function(data) {
              if (data.success) {
                alert('EMI Plan deleted');
                $state.go('app.entities', {
                  mode: $scope.mode,
                  type: 'emi_plan',
                });
              } else {
                alert(data.errors);
              }
            })
            .error(function() {
              alert('There was an error while deleting the EMI Plan');
            });
        },
      };

      $scope.toJson = function(data) {
        return angular.toJson(data, 4);
      };
      $scope.open = {
        terminalEdit: function(terminal) {
          var modalInstance = $modal.open({
            templateUrl: 'editTerminal.html',
            controller: 'editTerminalModalCtrl',
            resolve: {
              current: function() {
                return terminal;
              },
            },
          });
          modalInstance.result.then(function(input) {
            delete input.merchant_id;
            $scope.terminal.edit(input.id, input);
          }, $.noop);
        },
        offerEdit: function(offer) {
          var modalInstance = $modal.open({
            templateUrl: 'editOffer.html',
            controller: 'editOfferModalCtrl',
            resolve: {
              current: function() {
                return Object.assign({}, offer);
              },
            },
          });
          modalInstance.result.then(function(offer) {
            $scope.offer.edit(offer);
          }, $.noop);
        },
        disputeEdit: function(dispute) {
          var modalInstance = $modal.open({
            templateUrl: 'disputeModalContent.html',
            controller: 'DisputeModalCtrl',
            resolve: {
              current: function() {
                return Object.assign({}, dispute);
              },
              mode: function() {
                return $scope.mode;
              },
            },
          });
          modalInstance.result.then(function(dispute) {
            $scope.dispute.edit(dispute);
          }, $.noop);
        },
        iinEdit: function(iin) {
          var modalInstance = $modal.open({
            templateUrl: 'editIin.html',
            controller: 'editIinModalCtrl',
            resolve: {
              current: function() {
                return iin;
              },
            },
          });
          modalInstance.result.then(
            function(input) {
              $scope.iin.edit(input);
            },
            function() {}
          );
        },
        changePrimaryMerchant: function(terminal) {
          var modalInstance = $modal.open({
            templateUrl: 'changePrimaryMerchant.html',
            controller: 'changePrimaryMerchantCtrl',
            resolve: {
              current: function() {
                return terminal;
              },
            },
          });

          modalInstance.result.then(
            function(input) {
              $scope.terminal.changePrimaryMerchant(
                input.terminal_id,
                input.merchant_id
              );
            },
            function() {}
          );
        },
        terminalMerchantAssign: function(terminal) {
          var modalInstance = $modal.open({
            templateUrl: 'assignMerchantToTerminal.html',
            controller: 'assignMerchantToTerminalModalCtrl',
            resolve: {
              current: function() {
                return terminal;
              },
              subMerchants: function() {
                return $scope.entity.sub_merchants;
              },
              mode: function() {
                return $scope.mode;
              },
            },
          });

          modalInstance.result.then(
            function(input) {
              $scope.terminal.addSubMerchant(input.id, input.merchant_id);
            },
            function() {}
          );
        },
      };
      $scope.getKeys = function() {
        var keys = Object.keys($scope.entity);
        return keys;
      };

      function retryRefund(entityId) {
        return $http
          .post('/admin/generic/', {
            mode: $scope.mode,
            route_name: 'refund_verify_failed',
            url_params: {
              '{id}': entityId,
            },
          })
          .catch(function onRetryRefundFail() {
            return {
              data: {
                errors: ['There was an error while retrying to refund.'],
              },
            };
          });
      }

      $scope.onRetryRefund = function onRetryRefund(e) {
        e.preventDefault();

        var entityType = $scope.loadType,
          entityId = $scope.entity.id;

        if (entityType !== 'refund' || $scope.isRetryRefundProcessing) {
          return;
        }

        $scope.isRetryRefundProcessing = true;

        retryRefund(entityId).then(function onRetryRefundResp(resp) {
          var data = resp.data;

          $scope.alerts.resetAlerts();
          $scope.isRetryRefundProcessing = false;

          if (data.success) {
            $scope.alerts.addAlert('success', 'Refund Successful');
            $scope.entity.status = data.data.status;

            $scope.hasRetryRefund = hasRetryRefund($scope.entity);
          } else {
            angular.forEach(data.errors, function(error) {
              $scope.alerts.addAlert('danger', error);
            });
          }
        });
      };
    },
  ])
  .controller('editTerminalModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      // This is the current terminal current
      $scope.terminal = {
        gateway_access_code: current.gateway_access_code,
        gateway_merchant_id: current.gateway_merchant_id,
        gateway_terminal_id: current.gateway_terminal_id,
        merchant_id: current.merchant_id,
        id: current.id,
        gateway: current.gateway,
      };

      angular.forEach(current.type, function(type) {
        $scope.terminal['type[' + type + ']'] = '1';
      });

      $scope.ok = function(terminal) {
        $modalInstance.close(terminal);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editOfferModalCtrl', [
    '$scope',
    'dateFactory',
    '$modalInstance',
    'current',
    function($scope, dateFactory, $modalInstance, current) {
      $scope.date = dateFactory.getHandler($scope);
      $scope.date.dateOptions['showWeeks'] = false;

      $scope.offer = current;

      $scope.offer['linked_offer_ids'] = $scope.offer['linked_offer_ids']
        ? $scope.offer['linked_offer_ids'].join(',')
        : null;

      $scope.ok = function() {
        if ($scope.offer['iins']) {
          $scope.offer['iins'] = $scope.offer['iins'].split(',');
        }
        if ($scope.offer['linked_offer_ids']) {
          $scope.offer['linked_offer_ids'] = $scope.offer[
            'linked_offer_ids'
          ].split(',');
        }

        $scope.offer = {
          name: $scope.offer.name,
          iins: $scope.offer.iins,
          max_payment_count: $scope.offer.max_payment_count,
          linked_offer_ids: $scope.offer.linked_offer_ids,
          display_text: $scope.offer.display_text,
          error_message: $scope.offer.error_message,
          terms: $scope.offer.terms,
        };

        // Remove keys with null/empty value
        Object.keys($scope.offer).forEach(function(key) {
          if (!$scope.offer[key]) {
            delete $scope.offer[key];
          }
        });

        $modalInstance.close($scope.offer);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('editIinModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      // This is the current IIN
      $scope.iin = current;
      $scope.ok = function(iin) {
        $modalInstance.close(iin);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('changePrimaryMerchantCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    function($scope, $modalInstance, $http, current) {
      $scope.ok = function(terminal) {
        $modalInstance.close(terminal);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };

      $scope.terminal = {
        terminal_id: current.id,
        merchant_id: current.merchant_id,
      };
    },
  ])
  .controller('assignMerchantToTerminalModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    'current',
    'subMerchants',
    'mode',
    'alertsFactory',
    function(
      $scope,
      $modalInstance,
      $http,
      current,
      subMerchants,
      mode,
      alertsFactory
    ) {
      // This is the current terminal current
      $scope.subMerchants = subMerchants;
      $scope.alerts = alertsFactory.getHandler();

      $scope.deleteSubMerchant = function(merchantId) {
        var data = {
          route_name: 'terminal_remove_merchant',
          url_params: {
            '{id}': current.id,
            '{mid}': merchantId,
          },
          mode: mode,
        };
        var request = $http.delete('/admin/generic', {
          params: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.alerts.addAlert(
                'success',
                'Sub merchant unassigned from the terminal successfully'
              );

              // Page refresh
              window.location.reload();
            } else {
              $scope.alerts.addAlert(data.errors);
            }
          })
          .error(function() {
            $scope.alerts.addAlert(
              'danger',
              'There was an error while removing the merchant from the terminal'
            );
          });
      };

      $scope.terminal = {
        id: current.id,
        merchant_id: '',
      };
      $scope.ok = function(terminal) {
        $modalInstance.close(terminal);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
