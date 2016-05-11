"use strict";
//Entities Listing Controller
app.controller('EntitiesCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  'statusClass',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin, getStatusClass) {
    $scope.getStatusClass = getStatusClass;
    $scope.entity_type = $stateParams.type || 'payment';
    $scope.mode = $stateParams.mode;
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.count = 10;
    $scope.from = 0;
    $scope.to = 0;
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
    admin.identity().then(function (data) {
      $scope.admin = data;
    });
    $scope.timestamps = function (type) {
      var ts = 0;
      switch (type) {
      case 'from':
        ts = $scope.from;
        break;
      case 'to':
        ts = $scope.to;
        break;
      default:
        return 'Timestamp Error';
      }
      if (typeof ts === 'undefined') {
        return '';
      }
      return moment.unix(ts).format('L LTS');
    };
    var gatewayList = [
      'all',
      'amex',
      'atom',
      'axis_genius',
      'axis_migs',
      'billdesk',
      'ezeclick',
      'hdfc',
      'kotak',
      'mobikwik',
      'netbanking_hdfc',
      'netbanking_kotak',
      'paytm',
      'sharp',
      'wallet_payumoney',
      'wallet_payzapp'
    ];
    var walletList = [
      'all',
      'paytm',
      'mobikwik',
      'payzapp',
      'payumoney',
      'ezeclick'
    ];
    var booleanList = [
      'all',
      0,
      1
    ];
    var booleanList2 = [
      'all',
      true,
      false
    ];
    var statusList = [
      'all',
      'created',
      'authorized',
      'failed',
      'captured',
      'refunded'
    ];
    var methodList = [
      'all',
      'card',
      'emi',
      'netbanking',
      'wallet'
    ];
    // This is the list of available filters
    // len==1 means a text input, rest are drop-downs
    // This list is alphabetically sorted, take care to maintain that
    $scope.availableFilters = {
      adjustment: { merchant_id: ['Merchant Id'] },
      amex: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt Number']
      },
      axis_genius: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt Number']
      },
      axis_migs: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_TransactionNo: ['Transaction No'],
        vpc_ShopTransactionNo: ['Shop Transaction No'],
        vpc_TxnResponseCode: ['Txn Response Code'],
        vpc_ReceiptNo: ['Receipt No']
      },
      bank_account: {
        merchant_id: ['Merchant Id'],
        deleted: booleanList
      },
      balance: {},
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
        payment_id: ['Payment Id'],
        'TxnReferenceNo': ['Txn Reference No']
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
          'Discover'
        ],
        status: statusList,
        international: booleanList
      },
      customer: {
        merchant_id: ['Merchant Id'],
        email: ['Email'],
        active: booleanList
      },
      daily_settlement: {},
      emi_plan: {},
      hdfc: {
        payment_id: ['Payment Id'],
        received: booleanList,
        gateway_transaction_id: ['Gateway Transaction Id'],
        ref: ['Reference'],
        auth: ['Auth Code']
      },
      iin: {
        emi: booleanList,
        otp_read: booleanList,
        iin: ['Iin'],
        type: [
          'all',
          'credit',
          'debit',
          'unknown'
        ]
      },
      key: {},
      kotak: {},
      merchant: {
        email: ['Email'],
        activated: booleanList,
        hold_funds: booleanList,
        live: booleanList,
        international: booleanList,
        category: ['MCC Code'],
        pricing_plan_id: ['Pricing Plan Id'],
        receipt_email_enabled: booleanList,
        paytm: booleanList2,
        mobikwik: booleanList2,
        payzapp: booleanList2,
        payumoney: booleanList2,
        card: booleanList2,
        amex: booleanList2
      },
      methods: {
        merchant_id: ['Merchant Id'],
        card: booleanList,
        amex: booleanList,
        emi: booleanList,
        paytm: booleanList,
        mobikwik: booleanList,
        payzapp: booleanList,
        payumoney: booleanList
      },
      netbanking: {
        payment_id: ['Payment Id'],
        received: booleanList,
        caps_payment_id: ['Caps Payment Id'],
        bank_payment_id: ['Bank Reference Id'],
        int_payment_id: ['Int Payment Id']
      },
      order:{
        merchant_id: ['Merchant Id'],
        status: [
          'all',
          'created',
          'attempted',
          'paid',
        ],
        authorized: booleanList,
        receipt: ['Receipt Id']
      },
      payment: {
        bank: ['Bank Code'],
        email: ['Contact Email'],
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        card_id: ['Card Id'],
        method: methodList,
        refund_status: [
          'all',
          'null',
          'partial',
          'full'
        ],
        status: statusList,
        verified: [
          'all',
          'null',
          0,
          1,
          2,
        ],
        wallet: walletList,
        iin: ['Card IIN'],
        last4: ['Card Last 4']
      },
      paytm: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      pricing: {
        plan_id: ['Plan Id']
      },
      mobikwik: {
        payment_id: ['Payment Id'],
        received: booleanList
      },
      refund: {
        merchant_id: ['Merchant Id'],
        payment_id: ['Payment Id']
      },
      settlement: {
        transaction_id: ['Transaction Id'],
        merchant_id: ['Merchant Id'],
        status: ['all', 'created', 'failed', 'processed']
      },
      terminal: {
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        shared: booleanList
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
      },
      token: {
        token: ['Token'],
        customer_id: ['Customer Id'],
        merchant_id: ['Merchant Id'],
        card_id: ['Card Id']
      },
      wallet: {
        payment_id: ['Payment Id'],
        wallet: walletList
      },
      webhook: {
        merchant_id: ['Merchant Id'],
      }
    };

    var onWatchUpdate = function(newValue, oldValue) {
      if (newValue !== oldValue) {
        $scope.showTable();
      }
    };

    // This loop initializes the filters object
    for (var entity in $scope.availableFilters) {
      $scope.filters[entity] = {};
      var filters = $scope.availableFilters[entity];
      for (var filter in filters) {
        // dropdown
        // Only call watch if the property is a dropdown
        if (filters[filter].length > 1) {
          // The first value is the default
          $scope.filters[entity][filter] = filters[filter][0];
          $scope.$watch('filters.' + entity + '.' + filter, onWatchUpdate);
        }
      }
    }


    $scope.$watch('mode + entity_type + from + to', function () {
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
      $scope.generateTable();
    };
    $scope.prev = function () {
      clear('id');
      $scope.entity.skip -= $scope.count;
      $scope.generateTable();
    };

    $scope.getEntityListForUI = function () {
      var uiEntities = {};
      for (var entity in $scope.availableFilters) {
        uiEntities[entity] = entity.replace('_', ' ');
      }

      return uiEntities;
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
          angular.forEach(data.errors, function (value) {
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
      $scope.generateTable();
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
            'amex',
            'card',
            'emi',
            'paytm',
            'mobikwik',
            'payzapp',
            'payumoney'
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
        var val = filters[entity][filterName];
        if (val !== 'all' && val !== '' && val !== 'true' && val !== 'false') {
          query[filterName] = val;
        }
      }
      return query;
    }
    $scope.generateTable = function (csv) {
      if (!$scope.entity_type) {
        console.log('Error: No Entity Type Specified');
        return;
      }
      var query = generateQueryParams($scope.count, $scope.entity.skip, $scope.entity_type, $scope.filters, $scope.from, $scope.to);
      var url = '/admin/' + $scope.mode + '/fetchentity/' + $scope.entity_type;
      if (csv) {
        window.open(url + '/csv?' + $.param(query));
        return;
      }
      var request = $http.get(url, { params: query });
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.headings = data.data.headings;
          $scope.entity.items = data.data.items;
          $scope.entity.count = parseInt(data.data.count);
          $scope.entity.countStart = $scope.entity.skip + 1;
          if (data.data.count === 0)
            $scope.entity.countEnd = $scope.entity.countStart;
          else
            $scope.entity.countEnd = $scope.entity.countStart + $scope.entity.count - 1;
          $scope.allowPrev = $scope.entity.countStart != 1;
          $scope.allowNext = $scope.entity.count >= 10;
        } else {
          if (data.errors) {
            angular.forEach(data.errors, function (value) {
              $scope.alerts.addAlert('danger', value);
            });
          } else {
            $scope.alerts.addAlert('danger', null, true);
          }
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]);
