"use strict";

//Single Entity Details controller
app.controller('EntityDetailCtrl', [
  '$scope',
  '$http',
  '$stateParams',
  'alertsFactory',
  '$modal',
  'statusClass',
  'isStatusKey',
  'getState',
  'getType',
  'displayClass',
  'displayValue',
  function ($scope, $http, $stateParams, alertsFactory, $modal, statusClass, isStatusKey, getState, getType, displayClass, displayValue) {
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

    $scope.generate = function (entityType) {
      fetchEntity(entityType);
    };

    function fetchEntity(entityType) {
      var url = '/admin/' + $scope.mode + '/fetchentity/' + entityType + '/' + $scope.entity.id;
      var request = $http.get(url);
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.entity = data.data;
        } else {
          angular.forEach(data.errors, function (error) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function (res) {
        $scope.alerts.addAlert('danger', res ? res : null, true);
      });
    }

    // Terminal Specific actions
    $scope.terminal = {
      delete: function (id) {
        var request = $http.delete('/admin/' + $scope.mode + '/terminal/' + id);
        request.success(function (data) {
          if (data.success) {
            alert('Terminal deleted');
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while deleting the terminal');
        });
      },
      edit: function (id, data) {
        delete data.id;
        // Lets remove all the empty variables
        for (var i in data) {
          if (data[i] === '' || data[i] === null) {
            delete data[i];
          }
        }
        var request = $http.put('/admin/' + $scope.mode + '/terminal/' + id, data);
        request.success(function (data) {
          if (data.success) {
            alert('Terminal edit successfully');
            window.location.reload();
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while editing the terminal');
        });
      },
      disable: function (id) {
        var data = {toggle : 0};
        var request = $http.put('/admin/' + $scope.mode + '/terminal/' + id + '/toggle', data);
        request.success(function (data) {
          if (data.success) {
            alert('Terminal disabled');
            window.location.reload();
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while disabling the terminal');
        });
      },
      enable: function (id) {
        var data = {toggle : 1};
        var request = $http.put('/admin/' + $scope.mode + '/terminal/' + id + '/toggle', data);
        request.success(function (data) {
          if (data.success) {
            alert('Terminal enabled');
            window.location.reload();
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while enabling the terminal');
        });
      },
      addSubMerchant: function(id, merchant_id) {
        var request = $http.put('/admin/' + $scope.mode + '/terminal/' + id + '/merchant/' + merchant_id);

        request.success(function (data) {
          if (data.success) {
            $scope.alerts.addAlert('success', 'Sub Merchant added successfully');

            $scope.entity.sub_merchants.unshift(merchant_id);

            // Page refresh
            window.location.reload();
          } else {
            alert(data.errors);
          }
        }).error(function () {
          $scope.alerts.addAlert('danger', 'There was an error while adding the merchant to the terminal');
        });
      },
      changePrimaryMerchant: function (terminal_id, merchant_id) {
        var url = '/admin/' + $scope.mode + '/terminal/' + terminal_id + '/reassign';
        var request = $http.put(url, {
          merchant_id: merchant_id
        });

        request.success(function (data) {
          if (data.success) {
            $scope.alerts.addAlert('success', 'Primary merchant changed successfully');

            // Page refresh
            window.location.reload();
          }
          else {
            alert(data.errors);
          }
        }).error(function () {
          $scope.alerts.addAlert('danger', 'There was an issue while changing the primary merchant');
        });
      }
    };

    // IIN Specific actions
    $scope.iin = {
      delete: function (id) {
        var request = $http.delete('/admin/' + $scope.mode + '/iin/' + id);
        request.success(function (data) {
          if (data.success) {
            alert('IIN deleted');
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while deleting the IIN');
        });
      },
      edit: function (iin) {
        var iinId = iin.iin;

        iin = {
          category: iin.category,
          country: iin.country,
          emi: iin.emi ? 1 : 0,
          issuer_name: iin.issuer_name,
          issuer: iin.issuer,
          trivia: iin.trivia,
          network: iin.network,
          type: iin.type
        };
        // Lets remove all the empty variables
        for (var i in iin) {
          if (iin[i] === '' || iin[i] === null) {
            delete iin[i];
          }
        }
        var request = $http.put('/admin/iin/' + iinId, iin);
        request.success(function (data) {
          if (data.success) {
            alert('IIN edit successfully');
            window.location.reload();
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while editing the terminal');
        });
      }
    };

    // EMI Specific actions
    $scope.emi = {
      delete: function (id) {
        // EMI plans are also modeless
        var request = $http.delete('/admin/emi/' + id);
        request.success(function (data) {
          if (data.success) {
            alert('EMI Plan deleted');
          } else {
            alert(data.errors);
          }
        }).error(function () {
          alert('There was an error while deleting the EMI Plan');
        });
      }
    };


    $scope.toJson = function (data) {
      return angular.toJson(data, 4);
    };
    $scope.open = {
      terminalEdit: function (terminal) {
        var modalInstance = $modal.open({
          templateUrl: 'editTerminal.html',
          controller: 'editTerminalModalCtrl',
          resolve: {
            current: function () {
              return terminal;
            }
          }
        });
        modalInstance.result.then(function (input) {
          delete input.merchant_id;
          $scope.terminal.edit(input.id, input);
        }, $.noop);
      },
      iinEdit: function (iin) {
        var modalInstance = $modal.open({
          templateUrl: 'editIin.html',
          controller: 'editIinModalCtrl',
          resolve: {
            current: function () {
              return iin;
            }
          }
        });
        modalInstance.result.then(function (input) {
          $scope.iin.edit(input);
        }, function () {
        });
      },
      changePrimaryMerchant: function (terminal) {
        var modalInstance = $modal.open({
          templateUrl: 'changePrimaryMerchant.html',
          controller: 'changePrimaryMerchantCtrl',
          resolve: {
            current: function () {
              return terminal;
            }
          }
        });

        modalInstance.result.then(function (input) {
            $scope.terminal.changePrimaryMerchant(input.terminal_id, input.merchant_id);
        }, function() {
        });
      },
      terminalMerchantAssign: function (terminal) {
        var modalInstance = $modal.open({
          templateUrl: 'assignMerchantToTerminal.html',
          controller: 'assignMerchantToTerminalModalCtrl',
          resolve: {
            current: function () {
              return terminal;
            },
            subMerchants: function () {
              return $scope.entity.sub_merchants;
            },
            mode: function () {
              return $scope.mode;
            }
          }
        });

        modalInstance.result.then(function (input) {
            $scope.terminal.addSubMerchant(input.id, input.merchant_id);
        }, function() {
        });
      }
    };
    $scope.getKeys = function () {
      var keys = Object.keys($scope.entity);
      return keys;
    };
  }
]).controller('editTerminalModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  function ($scope, $modalInstance, $http, current) {
    // This is the current terminal current
    $scope.terminal = {
      gateway_access_code: current.gateway_access_code,
      gateway_merchant_id: current.gateway_merchant_id,
      gateway_terminal_id: current.gateway_terminal_id,
      id: current.id,
      card: current.card,
      gateway: current.gateway
    };
    $scope.ok = function (terminal) {
      $modalInstance.close(terminal);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('editIinModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  function ($scope, $modalInstance, $http, current) {
    // This is the current IIN
    $scope.iin = current;
    $scope.ok = function (iin) {
      $modalInstance.close(iin);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('changePrimaryMerchantCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  function ($scope, $modalInstance, $http, current) {

    $scope.ok = function (terminal) {
        $modalInstance.close(terminal);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };

    $scope.terminal = {
        terminal_id: current.id,
        merchant_id: current.merchant_id
    };
  }
]).controller('assignMerchantToTerminalModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  'subMerchants',
  'mode',
  'alertsFactory',
  function ($scope, $modalInstance, $http, current, subMerchants, mode, alertsFactory) {
    // This is the current terminal current
    $scope.subMerchants = subMerchants;
    $scope.alerts = alertsFactory.getHandler();

    $scope.deleteSubMerchant = function (merchantId) {
      var request = $http.delete('/admin/' + mode + '/terminal/' + current.id + '/merchant/' + merchantId);

      request.success(function (data) {
        if (data.success) {
          var index = $scope.subMerchants.indexOf(merchantId);
          if (index > -1) {
            $scope.subMerchants.splice(index, 1);
          }

          $scope.alerts.addAlert('success', 'Sub merchant unassigned from the terminal successfully');

          // Page refresh
          window.location.reload();
        }
        else {
          $scope.alerts.addAlert(data.errors);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', 'There was an error while removing the merchant from the terminal');
      });
    };

    $scope.terminal = {
        id: current.id,
        merchant_id : ''
    };
    $scope.ok = function (terminal) {
        $modalInstance.close(terminal);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
  }
]);
