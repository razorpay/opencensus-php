//Entities Listing Controller
app.controller('EntitiesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams) {
    $scope.entity_type = $stateParams.type;
    $scope.mode = $stateParams.mode;
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.count = 10;
    $scope.from = 0;
    $scope.to   = 0;
    $scope.filters = {};
    $scope.headings = [];
    $scope.refreshTable = true;
    $scope.entity = {
      items: {},
      id: '',
      count: 0,
      countStart: 0,
      countEnd: 0,
      skip: 0
    };

    $scope.minTimestamp = moment('2015-01-01').unix();
    $scope.maxTimestamp = moment().unix();

    $scope.timestamps = function (type) {

      var ts = 0;
      switch (type)
      {
        case 'from':
          ts = $scope.from;
          break;
        case 'to':
          ts = $scope.to;
          break;
        default:
          return 'Timestamp Error';
      }

      if (typeof ts === 'undefined')
      {
        return '';
      }
      return moment.unix(ts).format('L LTS');
    }

    var gatewayList = [
      'all',
      'atom',
      'axis_genius',
      'axis_migs',
      'billdesk',
      'hdfc',
      'kotak',
      'paytm',
      'mobikwik',
      'netbanking_hdfc',
      'sharp'
    ];

    var booleanList = ['all', 0, 1];

    var statusList = [
          'all',
          'authorized',
          'failed',
          'captured',
          'refunded'
    ];

    // This is the list of available filters
    // len==1 means a text input, rest are drop-downs
    // This list is alphabetically sorted, take care to maintain that
    $scope.availableFilters = {
      adjustment: { merchant_id: ['Merchant Id'] },
      axis_genius: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      axis_migs: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      billdesk: {
        AuthStatus: [
          'all',
          '0001',
          '0300',
          '0002',
          '0399',
          'NA'
        ],
        received: booleanList,
        payment_id: ['Payment Id']
      },
      card: {
        merchant_id: ['Merchant Id'],
        iin: ['IIN'],
        last4: ['last4'],
        network: [
          'all',
          'Visa',
          'MasterCard',
          'Maestro',
          'Diners Club',
          'American Express',
          'RuPay',
          'Unknown',
          'Discover',
        ],
        status: statusList
      },
      hdfc: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      merchant: {
        activated: booleanList,
        hold_funds: booleanList,
        live: booleanList,
        international: booleanList,
        category: ['MCC Code'],
        receipt_email_enabled: booleanList,
        paytm: [
          'all',
          true,
          false
        ],
        mobikwik: [
          'all',
          true,
          false
        ],
        card: [
          'all',
          true,
          false
        ]
      },
      netbanking: {
        payment_id: ['Payment Id'],
        received: booleanList,
        caps_payment_id: ['Caps Payment Id']
      },
      payment: {
        bank: ['Bank Code'],
        email: ['Contact Email'],
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        card_id: ['Card Id'],
        method: [
          'all',
          'card',
          'netbanking',
          'wallet'
        ],
        refund_status: [
          'all',
          'partial',
          'full'
        ],
        status: statusList,
        verified: [
          'all',
          0,
          1
        ],
        wallet: [
          'all',
          'paytm',
          'mobikwik'
        ],
        iin: ['Card IIN'],
        last4: ['Card Last 4'],
      },
      paytm: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      mobikwik: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      refund: { merchant_id: ['Merchant Id'] },
      terminal: {
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        shared: ['all', 0, 1]
      },
      transaction: {
        entity_id: ['Payment/Refund/Settlement Id'],
        merchant_id: ['Merchant Id'],
        settled: booleanList,
        settlement_id: ['Settlement Id'],
        type: [
          'all',
          'payment',
          'refund',
          'settlement',
          'adjustment'
        ]
      }
    };

    // This loop initializes the filters object
    for (var entity in $scope.availableFilters) {
      $scope.filters[entity] = {};
      var filters = $scope.availableFilters[entity];
      for (var filter in filters) {
        var def;
        // dropdown
        // Only call watch if the property is a dropdown
        if (filters[filter].length > 1) {
          // The first value is the default
          $scope.filters[entity][filter] = filters[filter][0];
          $scope.$watch('filters.' + entity + '.' + filter, function (newValue, oldValue) {
            if (newValue !== oldValue) {
              $scope.showTable();
            }
          });
        }
      }
    }
    $scope.$watch('mode + entity_type + count + from + to', function (x) {
      $state.go('app.entities', {
        mode: $scope.mode,
        type: $scope.entity_type
      }, { notify: false });
      $scope.showTable();
    });
    $scope.notSorted = function (obj) {
      if (!obj) {
        return [];
      }
      var data = Object.keys(obj);
      data.splice(-1, 1);
      return data;
    };
    $scope.displayFilter = function (val) {
      var labels = {
        0: 'no',
        1: 'yes',
        true: 'yes',
        false: 'no',
        '0001': 'BillDesk Cancel',
        '0300': 'Success',
        '0002': 'Bank Pending',
        '0399': 'Bank Cancel Auth Error',
        'NA': 'Invalid Input'
      };
      if (val in labels) {
        return labels[val];
      } else {
        return val;
      }
    };
    $scope.next = function () {
      clear('id');
      $scope.entity.skip += $scope.count;
      generateTable();
    };
    $scope.prev = function () {
      clear('id');
      $scope.entity.skip -= $scope.count;
      generateTable();
    };
    $scope.search = function () {
      clear('skip');
      var request = $http.get('/admin/' + $scope.mode + '/fetchentity/' + $scope.entity_type + '/' + $scope.entity.id);
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          var entity = data.data.entity;
          var stateArray = {
              'payment': 'app.payments',
              'merchant': 'app.merchants.detail'
            }, state = 'app.entitiesdetail';
          if (entity in stateArray) {
            state = stateArray[entity];
          }
          $state.go(state, {
            mode: $scope.mode,
            id: data.data.id,
            type: entity
          });
        } else {
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    if ($stateParams.type && $stateParams.id) {
      $scope.entity.id = $stateParams.id;
      $scope.entity_type = $stateParams.type;
      $scope.search();
    }
    function clear(field) {
      if (field == 'id')
        $scope.entity.id = '';
      else
        $scope.entity.skip = 0;
    }
    $scope.showTable = function () {
      clear('id');
      clear('skip');
      generateTable();
    };
    $scope.getState = function (type, force) {
      switch (type) {
      case 'merchant_id':
      case 'merchant':
        return 'app.merchants.detail({id: value})';
      case 'payment_id':
      case 'payment':
        return 'app.payments({id:value, mode:mode})';
      default:
        if (force === true) {
          return 'app.entitiesdetail({id:value, mode:mode, type: row.entity})';
        }
        if (type.substr(-3) === '_id') {
          return 'app.entitiesdetail({id:value, mode:mode, type: key})';
        } else {
          return '-';
        }
      }
    };
    function generateQueryParams(count, skip, entity, filters, from, to) {
      var query = {
        count: count,
        skip: skip
      };

      if (from !== 0) {
        query.from = from;
      }

      if (to !== 0) {
        query.to = to;
      }

      // We send the methods param in a JSON encoded format
      if (entity === 'merchant') {
        var methods = {}, validMethods = [
            'paytm',
            'mobikwik',
            'card'
          ];
        for (var i in validMethods) {
          var method = validMethods[i];
          var value = filters.merchant[method];
          if (value && value !== 'all') {
            methods[method] = value;
          }
        }
        filters.merchant.methods = JSON.stringify(methods);
      }
      for (var filterName in filters[entity]) {
        var value = filters[entity][filterName];
        if (value !== 'all' && value !== '' && value !== 'true' && value !== 'false') {
          query[filterName] = value;
        }
      }
      return query;
    }
    function generateTable() {
      if (!$scope.entity_type) {
        console.log('Error: No Entity Type Specified');
        return;
      }
      var query = generateQueryParams($scope.count, $scope.entity.skip, $scope.entity_type, $scope.filters, $scope.from, $scope.to);
      var request = $http.get('/admin/' + $scope.mode + '/fetchentity/' + $scope.entity_type, {
        params: query
      });

      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.headings = data.data.headings;
          $scope.entity.items = data.data.items;
          $scope.entity.count = parseInt(data.data.count);
          $scope.entity.countStart = $scope.entity.skip + 1;
          if (data.data.count == 0)
            $scope.entity.countEnd = $scope.entity.countStart;
          else
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count - 1;
          $scope.allowPrev = $scope.entity.countStart != 1;
          $scope.allowNext = $scope.entity.count >= 10;
        } else {
          if (data.errors) {
            angular.forEach(data.errors, function (value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          } else {
            $scope.alerts.addAlert('danger', null, true);
          }
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
]);
