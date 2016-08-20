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
  'getState',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin, getStatusClass, getState) {
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
      'ebs',
      'cybersource',
      'ezeclick',
      'hdfc',
      'kotak',
      'mobikwik',
      'netbanking_hdfc',
      'netbanking_kotak',
      'paytm',
      'sharp',
      'wallet_payumoney',
      'wallet_payzapp',
      'wallet_olamoney'
    ];
    var walletList = [
      'all',
      'paytm',
      'mobikwik',
      'payzapp',
      'payumoney',
      'olamoney',
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
      app_token: {
        customer_id: ['Customer Id'],
        device_token: ['Device Token'],
        merchant_id: ['Merchant Id']
      },
      axis_genius: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt Number']
      },
      axis_migs: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt No'],
        vpc_ShopTransactionNo: ['Shop Transaction No'],
        vpc_TransactionNo: ['Transaction No'],
        vpc_TxnResponseCode: ['Txn Response Code'],
        vpc_3DSstatus: [
            'all',
            'Y',
            'N',
            'U',
            'A'
        ],
      },
      bank_account: {
        deleted: booleanList,
        merchant_id: ['Merchant Id']
      },
      balance: {},
      ebs: {},
      billdesk: {
        AuthStatus: [
          'all',
          '0001',
          '0300',
          '0002',
          '0399',
          'NA'
        ],
        BankReferenceNo: ['Bank Reference No'],
        payment_id: ['Payment Id'],
        received: booleanList,
        RefStatus: ['Refund Status'],
        RefundId: ['Billdesk Refund Id'],
        TxnReferenceNo: ['Txn Reference No']
      },
      card: {
        global_card_id: ['Global Card Id'],
        iin: ['IIN'],
        international: booleanList,
        last4: ['last4'],
        merchant_id: ['Merchant Id'],
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
        vault: ['Vault'],
        vault_token: ['Vault Token'],
      },
      customer: {
        merchant_id: ['Merchant Id'],
        email: ['Email'],
        active: booleanList,
        contact: ['Contact']
      },
      cybersource: {
        payment_id: ['Payment ID'],
        received: booleanList,
        ref: ['Reference'],
        capture_ref: ['Capture Reference']
      },
      daily_settlement: {},
      emi_plan: {},
      hdfc: {
        auth: ['Auth Code'],
        gateway_transaction_id: ['Gateway Transaction Id'],
        payment_id: ['Payment Id'],
        received: booleanList,
        ref: ['Reference']
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
      key: {
        merchant_id: ['Merchant Id']
      },
      merchant: {
        activated: booleanList,
        amex: booleanList2,
        card: booleanList2,
        category: ['MCC Code'],
        email: ['Email'],
        hold_funds: booleanList,
        international: booleanList,
        live: booleanList,
        mobikwik: booleanList2,
        paytm: booleanList2,
        payumoney: booleanList2,
        payzapp: booleanList2,
        olamoney: booleanList2,
        pricing_plan_id: ['Pricing Plan Id'],
        receipt_email_enabled: booleanList
      },
      methods: {
        amex: booleanList,
        card: booleanList,
        emi: booleanList,
        merchant_id: ['Merchant Id'],
        mobikwik: booleanList,
        paytm: booleanList,
        payumoney: booleanList,
        payzapp: booleanList,
        olamoney: booleanList
      },
      netbanking: {
        bank_payment_id: ['Bank Reference Id'],
        caps_payment_id: ['Caps Payment Id'],
        int_payment_id: ['Int Payment Id'],
        payment_id: ['Payment Id'],
        received: booleanList
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
        receipt_id: ['Receipt Id']
      },
      payment: {
        app_token: ['App Token'],
        bank: ['Bank Code'],
        card_id: ['Card Id'],
        customer_id: ['Customer Id'],
        global_customer_id: ['Global Customer Id'],
        email: ['Contact Email'],
        gateway: gatewayList,
        global_token: ['Global Token'],
        iin: ['Card IIN'],
        international: booleanList,
        last4: ['Card Last 4'],
        merchant_id: ['Merchant Id'],
        method: methodList,
        notes: ['Notes'],
        refund_status: [
          'all',
          'null',
          'partial',
          'full'
        ],
        save: booleanList,
        status: statusList,
        token: ['Token'],
        verified: [
          'all',
          'null',
          0,
          1,
          2
        ],
        wallet: walletList
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
        merchant_id: ['Merchant Id'],
        status: ['all', 'created', 'failed', 'processed'],
        transaction_id: ['Transaction Id']
      },
      settlement_details: {
        merchant_id: ['Merchant Id'],
        settlement_id: ['Settlement Id']
      },
      terminal: {
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        shared: booleanList
      },
      transaction: {
        entity_id: ['Payment/Refund/Settlement Id'],
        merchant_id: ['Merchant Id'],
        reconciled: booleanList,
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
        bank: ['Bank Code'],
        card_id: ['Card Id'],
        customer_id: ['Customer Id'],
        merchant_id: ['Merchant Id'],
        method: methodList,
        terminal_id: ['Terminal Id'],
        token: ['Token'],
        wallet: walletList
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
    $scope.getState = getState;
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
          $scope.allowNext = $scope.entity.count >= $scope.count;
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
