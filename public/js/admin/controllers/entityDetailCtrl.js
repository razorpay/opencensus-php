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
  function ($scope, $http, $stateParams, alertsFactory, $modal, statusClass, isStatusKey, getState) {
    //Intialise alerts and scope functions
    $scope.getStatusClass = statusClass;
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
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
    $scope.getState = getState;
    $scope.displayValue = function (value, type) {
      // Set timezone to IST
      moment().zone(5.5);
      switch (type) {
      case 'timestamp':
        return moment(value * 1000).format('D MMM YYYY h:mm:ss a (ddd) ') + 'IST';
      case 'amount':
        return 'INR ' + (value / 100).toFixed(2);
      default:
        if (value === null) {
          return 'null';
        } else if (value === '') {
          return '"\u2000"';
        } else {
          return value;
        }
      }
    };
    $scope.displayClass = function (value) {
      if (value === null) {
        return 'label label-warning';
      } else if (value === '') {
        return 'label label-info';
      } else {
        return '';
      }
    };
    // Removes _id from end
    $scope.getEntity = function (key) {
      return key.substr(0, key.length - 3);
    };
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
      }
    };
    $scope.getKeys = function () {
      var keys = Object.keys($scope.entity);
      return keys;
    };
    $scope.getType = function (key, value) {
      var entity = key.substr(0, key.length - 3);
      var isTimestamp = function (key) {
        return key.substr(-3) === '_at';
      };
      // These have their own views
      var specialEntities = [
        'merchant_id',
        'payment_id'
      ];
      var isId = function (key) {
        var validEntities = [
          'adjustment',
          'amex',
          'atom',
          'axis_genius',
          'axis_migs',
          'balance',
          'bank_account',
          'bank_account',
          'billdesk',
          'ebs',
          'card',
          'customer',
          'daily_settlement',
          'emi_plan',
          'hdfc',
          'iin',
          'merchant',
          'methods',
          'mobikwik',
          'netbanking',
          'payment',
          'paytm',
          'pricing',
          'refund',
          'settlement',
          'settlement_details',
          'terminal',
          'token',
          'transaction',
          'wallet',
          'webhook'
        ];
        // It needs to be suffixed with _id
        // and be a valid entity name for this to work
        return key.substr(-3) === '_id' && validEntities.indexOf(entity) > -1;
      };
      // Timestamps could be blank, which is why
      // we consider its value as well
      if (value && isTimestamp(key)) {
        return 'timestamp';
      }  // All other entity links are considered here
      else if (isId(key)) {
        if (specialEntities.indexOf(key) > -1) {
          return $scope.getEntity(key);
        } else {
          return 'id';
        }
      }  // Unknown type is entity specific things, like currency
      else {
        return 'unknown';
      }
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
]);
