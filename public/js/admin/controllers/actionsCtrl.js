// Admin Actions Controller
app
  .controller('ActionsCtrl', [
    '$scope',
    '$http',
    'alertsFactory',
    'transformRequestAsFormPost',
    '$modal',
    'admin',
    'utils',
    '$state',
    function(
      $scope,
      $http,
      alertsFactory,
      transformRequestAsFormPost,
      $modal,
      admin,
      utils,
      $state
    ) {
      admin.identity().then(function(data) {
        $scope.admin = data;
        $scope.response = null;
        $scope.alerts = alertsFactory.getHandler();
        $scope.addIIN = function(iin) {
          iin.emi = iin.emi ? 1 : 0;

          var data = {
            route_name: 'iin_add',
            body: iin,
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
                  'IIN added successfully. Response: ' +
                    JSON.stringify(data.data),
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
        $scope.addEMI = function(emi) {
          var data = {
            route_name: 'emi_plan_add',
            body: emi,
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
                  'EMI Plan added successfully',
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
        $scope.generateNetBankingRefunds = function(params) {
          params.method = 'netbanking';
          if (params.email_self) {
            params.email = $scope.admin.email;
          }
          delete params.email_self;
          var data = {
            route_name: 'refund_generate_excel',
            body: params,
            mode: params.mode,
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
                  params.bank.toUpperCase() +
                    ' Refunds Excel Generated (Count = ' +
                    data.data.count +
                    ')',
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
        $scope.generateEmiFiles = function(params) {
          if (params.email_self) {
            params.email = $scope.admin.email;
          }
          delete params.email_self;
          var data = {
            route_name: 'emi_generate_excel',
            body: params,
            mode: params.mode,
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
                  params.bank.toUpperCase() +
                    ' Emi Excel Generated (Count = ' +
                    data.data.count +
                    ')',
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
        $scope.toJson = function(data) {
          return angular.toJson(data, 4);
        };
        $scope.apiRequest = function(data, url) {
          if (typeof url === 'undefined') {
            var url = '/api/' + data.url;
          }

          data = data.form;

          var req = $http.post(url, data, {
            transformRequest: angular.identity,
            // We set this to undefined to let angular auto-detect this
            // And convert to a multipart file upload
            headers: {
              'Content-Type': undefined,
            },
          });

          req
            .success(function(data) {
              if (data.success) {
                $scope.alerts.resetAlerts();
                $scope.response = data;
                $scope.alerts.addAlert(
                  'success',
                  'API Request successful',
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                $scope.response = null;
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.resetAlerts();
              $scope.alerts.addAlert(
                'danger',
                'The API request failed on the dashboard side.',
                true
              );
            });
        };

        $scope.reconUpload = function(data) {
          var url = data.url;
          data = data.form;

          var req = $http.post(url, data, {
            transformRequest: angular.identity,

            // We set this to undefined to let angular auto-detect this
            // And convert to a multipart file upload
            headers: {
              'Content-Type': undefined,
            },
          });

          req
            .success(function(data) {
              if (data.success) {
                $scope.alerts.resetAlerts();
                $scope.response = data;
                $scope.alerts.addAlert(
                  'success',
                  'Reconciliation Response successful',
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                $scope.response = null;
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.resetAlerts();
              $scope.alerts.addAlert(
                'danger',
                'The API request failed on the dashboard side.',
                true
              );
            });
        };
        $scope.authorizeFailedPayment = function(payment_id, mode) {
          var data = {
            route_name: 'payment_authorize_failed',
            url_params: {
              '{id}': payment_id,
            },
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
                var payment = JSON.stringify(data.data.payment);
                $scope.alerts.addAlert(
                  'success',
                  'Payment Authorized Successfully: ' + payment,
                  true
                );
              } else {
                $scope.alerts.resetAlerts();
                angular.forEach(data.errors, function(value, key) {
                  $scope.alerts.addAlert('danger', value);
                });
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
        $scope.openSetlUpload = function() {
          var modalInstance = $modal.open({
            templateUrl: 'uploadSetlRecon.html',
            controller: 'ApiRequestCtrl',
          });
          modalInstance.result.then(function(data) {
            $scope.apiRequest(data, '/settlements/reconcile');
          }, $.noop);
        };

        $scope.openReconUpload = function() {
          var modalInstance = $modal.open({
            templateUrl: 'uploadRecon.html',
            controller: 'ReconUploadCtrl',
          });
          modalInstance.result.then($scope.reconUpload, $.noop);
        };

        $scope.openApiRequest = function() {
          var modalInstance = $modal.open({
            templateUrl: 'makeApiCallModalContent.html',
            controller: 'ApiRequestCtrl',
          });
          modalInstance.result.then(function(data) {
            $scope.apiRequest(data);
          }, $.noop);
        };
        $scope.openGenerateRefund = function() {
          var modalInstance = $modal.open({
            templateUrl: 'refundGenerateModalContent.html',
            controller: 'generateRefundModalCtrl',
          });
          modalInstance.result.then($scope.generateNetBankingRefunds, $.noop);
        };
        $scope.openGenerateEmiFiles = function() {
          var modalInstance = $modal.open({
            templateUrl: 'emiGenerateModalContent.html',
            controller: 'generateEmiModalCtrl',
          });
          modalInstance.result.then($scope.generateEmiFiles, $.noop);
        };
        $scope.openAddIIN = function() {
          var modalInstance = $modal.open({
            templateUrl: 'addIINModalContent.html',
            controller: 'addIINModalCtrl',
          });
          modalInstance.result.then($scope.addIIN, $.noop);
        };
        $scope.openAddEMI = function() {
          var modalInstance = $modal.open({
            templateUrl: 'addEMIModalContent.html',
            controller: 'addEMIModalCtrl',
          });
          modalInstance.result.then($scope.addEMI, $.noop);
        };
        $scope.confirmUser = function(email) {
          var data = {
            route_name: 'user_confirm_by_data',
            body: {
              email: email,
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
                  'User confirmed successfully',
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
        $scope.openConfirmUser = function() {
          var modalInstance = $modal.open({
            templateUrl: 'confirmUserModal.html',
            controller: 'confirmUserModalCtrl',
          });
          modalInstance.result.then($scope.confirmUser, $.noop);
        };
        $scope.openAuthorizeFailedPayment = function() {
          var modalInstance = $modal.open({
            templateUrl: 'authorizeFailedPaymentModalContent.html',
            controller: 'authorizeFailedPaymentModalCtrl',
          });
          modalInstance.result.then(function(data) {
            $scope.authorizeFailedPayment(data.id, data.mode);
          }, $.noop);
        };
        $scope.triggerError = function() {
          var request = $http.get('/admin/generic', {
            params: {
              route_name: 'dummy_critical_error',
            },
          });
          request
            .success(function(data) {
              if (!data.success) {
                $scope.alerts.addAlert(
                  'success',
                  'Error triggerred successfully',
                  true
                );
              } else {
                $scope.alerts.addAlert(
                  'danger',
                  'Error not triggerred successfully',
                  true
                );
              }
            })
            .error(function() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };

        $scope.retrySettlements = function retrySettlements(idList) {
          var request = $http
            .post('/admin/generic', {
              route_name: 'setl_retry',
              body: { settlement_ids: idList },
            })
            .success(function onRetrySettlementsSuccess(data) {
              if (data.success) {
                $scope.alerts.addAlert(
                  'success',
                  'Settlements Retry Successfull',
                  true
                );
              } else {
                $scope.alerts.addAlert(
                  'danger',
                  'Settlements Retry Failed',
                  true
                );
              }
            })
            .error(function onRetrySettlementsFail() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };

        $scope.updateGSTIN = function updateGSTIN(data) {
          $http
            .put('/admin/generic', {
              route_name: 'merchant_invoice_update_gstin',
              mode: data.mode,
              url_params: {
                '{id}': data.merchantId,
              },
              body: { invoice_number: data.invoiceNumber },
            })
            .success(function onUpdateGSTINSuccess(data) {
              if (data.success) {
                if (utils.isWorkflow(data.data)) {
                  $state.go('app.workflows.actions.detail', {
                    action_id: data.data.id,
                  });
                  return;
                }
                $scope.alerts.addAlert(
                  'success',
                  'Update GSTIN Successfull',
                  true
                );
              } else {
                $scope.alerts.addAlert('danger', 'Update GSTIN Failed', true);
              }
            })
            .error(function onUpdateGSTINFail() {
              $scope.alerts.addAlert('danger', null, true);
            });
        };
      });
      $scope.addDisputeReason = function(dispute_reason) {
        var data = {
          route_name: 'dispute_reason_create',
          body: dispute_reason,
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
                'Dispute Reason added successfully. Response: ' +
                  JSON.stringify(data.data),
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.openConfirmUser = function() {
        var modalInstance = $modal.open({
          templateUrl: 'confirmUserModal.html',
          controller: 'confirmUserModalCtrl',
        });
        modalInstance.result.then($scope.confirmUser, $.noop);
      };
      $scope.openAuthorizeFailedPayment = function() {
        var modalInstance = $modal.open({
          templateUrl: 'authorizeFailedPaymentModalContent.html',
          controller: 'authorizeFailedPaymentModalCtrl',
        });
        modalInstance.result.then(function(data) {
          $scope.authorizeFailedPayment(data.id, data.mode);
        }, $.noop);
      };
      $scope.openAddSchedule = function() {
        var modalInstance = $modal.open({
          templateUrl: 'addScheduleModalContent.html',
          controller: 'addScheduleModalCtrl',
        });
        modalInstance.result.then($scope.addSchedule, $.noop);
      };
      $scope.addSchedule = function(schedule) {
        var data = {
          route_name: 'schedule_create',
          body: schedule,
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
                'Schedule added successfully',
                true
              );
            } else {
              $scope.alerts.resetAlerts();
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          })
          .error(function() {
            $scope.alerts.addAlert('danger', null, true);
          });
      };
      $scope.openRertySettlements = function openRertySettlements() {
        var modalInstance = $modal.open({
          templateUrl: 'retrySettlementsModalContent.html',
          controller: 'retrySettlementsController',
        });
        modalInstance.result.then($scope.retrySettlements, $.noop);
      };
      $scope.openGSTINUpdate = function openGSTINUpdate() {
        $modal
          .open({
            templateUrl: 'updateGSTINModalContent.html',
            controller: 'updateGSTINController',
          })
          .result.then($scope.updateGSTIN, $.noop);
      };
      $scope.openDisputeReasonAdd = function() {
        var modalInstance = $modal.open({
          templateUrl: 'addDisputeReason.html',
          controller: 'addDisputeReasonCtrl',
        });
        modalInstance.result.then($scope.addDisputeReason, $.noop);
      };
    },
  ])
  .controller('addIINModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.ok = function(iin) {
        $modalInstance.close(iin);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('addEMIModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.emi = {
        bank: 'HDFC',
        duration: 3,
        methods: '',
      };
      $scope.ok = function(emi) {
        $modalInstance.close(emi);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('verifyPaymentModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.ok = function(id) {
        $modalInstance.close(id);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('archiveMerchantModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance, $http) {
      $scope.ok = function(id) {
        $modalInstance.close(id);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('confirmUserModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance, $http) {
      $scope.ok = function(email) {
        $modalInstance.close(email);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('authorizeFailedPaymentModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.mode = 'live';
      $scope.ok = function(id, mode) {
        $modalInstance.close({
          id: id,
          mode: mode,
        });
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('ApiRequestCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.url = '';
      $scope.data = {
        mode: 'test',
        method: 'GET',
        auth: 'admin',
        merchant_id: '',
        content_type: 'application/x-www-form-urlencoded',
        body: '',
        file: null,
        file_name: null,
      };

      $scope.ok = function(url, data) {
        var fd = new FormData();

        // If we are sending a file
        // We don't add the content type header
        // because this needs to be auto-generated
        if (data.file) {
          delete data.content_type;
        }

        // We push all data fields
        // into the formdata object
        for (var field in data) {
          var value = data[field];
          fd.append(field, value);
        }

        // We pass an instance of FormData
        // And the URL separately because extracting and deleting
        // items from formdata is not supported most browsers
        // including chrome
        $modalInstance.close({
          form: fd,
          url: url,
        });
      };

      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('ReconUploadCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.counter = Array;

      $scope.files = [];

      $scope.ok = function(mode, input) {
        var url = '/admin/' + mode + '/reconciliate';

        var data = {
          manual: 1,
          'attachment-count': input.files.length,
          gateway: input.gateway,
        };

        for (var i in input.files) {
          if (input.files[i]) {
            // attachment-X starts from 1
            // following mailgun conventions
            var num = parseInt(i) + 1;
            data['attachment-' + num] = input.files[i];
          }
        }

        var fd = new FormData();

        for (var field in data) {
          var value = data[field];
          fd.append(field, value);
        }

        $modalInstance.close({
          form: fd,
          url: url,
        });
      };

      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('generateRefundModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.bank = 'HDFC';
      $scope.mode = 'live';
      $scope.date = moment().format('YYYY-MM-DD');
      $scope.ok = function(date, from, to, bank, mode, email_self) {
        var tzGMTToIST = 19800;
        var data = {
          bank: bank,
          mode: mode,
          email_self: email_self,
        };
        if (from && to) {
          // Date from the date api is in GMT
          var fromInGMT = new Date(from).getTime() / 1000;
          var toInGMT = new Date(to).getTime() / 1000;

          // Subtract 19800 from GMT to convert timestamps to IST
          var fromInIST = fromInGMT - tzGMTToIST;
          var toInIST = toInGMT - tzGMTToIST;

          // Final variable to be sent
          data.from = fromInIST;
          data.to = toInIST;
        } else {
          // Final variable api expects is on
          data.on = date;
        }
        $modalInstance.close(data);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('generateEmiModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.mode = 'live';
      $scope.date = moment().format('YYYY-MM-DD');
      // Load bank list here

      $scope.ok = function(date, from, to, bank, mode, email_self) {
        var tzGMTToIST = 19800;
        var data = {
          bank: bank,
          mode: mode,
          email_self: email_self,
        };

        if (from && to) {
          // Date from the date api is in GMT
          var fromInGMT = new Date(from).getTime() / 1000;
          var toInGMT = new Date(to).getTime() / 1000;

          // Subtract 19800 from GMT to convert timestamps to IST
          var fromInIST = fromInGMT - tzGMTToIST;
          var toInIST = toInGMT - tzGMTToIST;

          // Final variable to be sent
          data.from = fromInIST;
          data.to = toInIST;
        } else {
          // Final variable api expects is on
          data.on = date;
        }
        $modalInstance.close(data);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('addScheduleModalCtrl', [
    '$scope',
    '$modalInstance',
    '$http',
    function($scope, $modalInstance, $http) {
      $scope.ok = function(schedule) {
        $modalInstance.close(schedule);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('retrySettlementsController', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.settlementIds = '';

      $scope.ok = function(ids) {
        var ids = ids && ids.trim(),
          idList = [];

        if (!ids) {
          return;
        }

        angular.forEach(ids.split(','), function(id) {
          id = id.trim();
          return id && idList.push(id);
        });

        if (idList.length === 0) {
          return;
        }

        $modalInstance.close(idList);
      };

      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('updateGSTINController', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.merchantId = '';
      $scope.invoiceNumber = '';
      $scope.mode = 'live';

      $scope.ok = function(merchantId, invoiceNumber, mode) {
        merchantId = merchantId.trim();
        invoiceNumber = invoiceNumber.trim();

        if (!merchantId || !invoiceNumber) {
          return;
        }

        $modalInstance.close({
          merchantId: merchantId,
          invoiceNumber: invoiceNumber,
          mode: mode,
        });
      };

      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('addDisputeReasonCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.ok = function(dispute_reason) {
        $modalInstance.close(dispute_reason);
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]);
