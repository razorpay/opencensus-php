'use strict';
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
  function(
    $scope,
    $http,
    alertsFactory,
    $state,
    $modal,
    $stateParams,
    admin,
    getStatusClass,
    getState
  ) {
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
      skip: 0,
    };
    $scope.minTimestamp = moment('2015-01-01').unix();
    $scope.maxTimestamp = moment().unix();
    admin.identity().then(function(data) {
      $scope.admin = data;
    });
    $scope.timestamps = function(type) {
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
      'aeps_icici',
      'axis_genius',
      'axis_migs',
      'billdesk',
      'ebs',
      'cybersource',
      'hitachi',
      'first_data',
      'ezeclick',
      'hdfc',
      'kotak',
      'mobikwik',
      'marketplace',
      'netbanking_hdfc',
      'netbanking_corporation',
      'netbanking_kotak',
      'netbanking_axis',
      'netbanking_icici',
      'netbanking_airtel',
      'netbanking_federal',
      'netbanking_indusind',
      'netbanking_rbl',
      'netbanking_pnb',
      'paytm',
      'sharp',
      'upi_icici',
      'upi_mindgate',
      'wallet_payumoney',
      'wallet_payzapp',
      'wallet_olamoney',
      'wallet_airtelmoney',
      'wallet_freecharge',
      'wallet_jiomoney',
      'wallet_sbibuddy',
      'wallet_openwallet',
      'wallet_mpesa',
    ];
    var walletList = [
      'all',
      'paytm',
      'mobikwik',
      'payzapp',
      'payumoney',
      'olamoney',
      'airtelmoney',
      'freecharge',
      'jiomoney',
      'sbibuddy',
      'ezeclick',
      'openwallet',
      'mpesa',
    ];
    var upiBankList = ['all', 'icici'];
    var booleanList = ['all', 0, 1];
    var booleanList2 = ['all', true, false];
    var statusList = [
      'all',
      'created',
      'authorized',
      'failed',
      'captured',
      'refunded',
    ];
    var methodList = [
      'all',
      'card',
      'emi',
      'netbanking',
      'wallet',
      'upi',
      'transfer',
      'bank_transfer',
    ];
    var gatewayFileTargetList = [
      'all',
      'rbl',
      'hdfc',
      'axis',
      'icici',
      'kotak',
      'federal',
    ];
    // This is the list of available filters
    // len==1 means a text input, rest are drop-downs
    // This list is alphabetically sorted, take care to maintain that
    $scope.availableFilters = {
      addon: {
        deleted: booleanList,
        invoice_id: ['Invoice Id'],
        merchant_id: ['Merchant Id'],
        subscription_id: ['Subscription Id'],
      },
      adjustment: { merchant_id: ['Merchant Id'] },
      amex: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt Number'],
      },
      app_token: {
        customer_id: ['Customer Id'],
        device_token: ['Device Token'],
        merchant_id: ['Merchant Id'],
      },
      axis_genius: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt Number'],
      },
      axis_migs: {
        payment_id: ['Payment Id'],
        received: booleanList,
        vpc_ReceiptNo: ['Receipt No'],
        vpc_ShopTransactionNo: ['Shop Transaction No'],
        vpc_TransactionNo: ['Transaction No'],
        vpc_TxnResponseCode: ['Txn Response Code'],
        vpc_3DSstatus: ['all', 'Y', 'N', 'U', 'A'],
      },
      balance: {},
      bank_account: {
        deleted: booleanList,
        entity_id: ['Entity Id'],
        merchant_id: ['Merchant Id'],
        type: ['all', 'customer', 'merchant'],
      },
      bank_transfer: {
        merchant_id: ['Merchant Id'],
        payment_id: ['Payment ID'],
        utr: ['UTR'],
        virtual_account_id: ['Virtual Account ID'],
        mode: ['all', 'neft', 'rtgs', 'ift', 'imps'],
        payer_account: ['Payer Account'],
        payer_ifsc: ['Payer IFSC'],
        payee_account: ['Payee Account'],
        payee_ifsc: ['Payee IFSC'],
        amount: ['Amount'],
      },
      batch: {
        merchant_id: ['Merchant Id'],
        status: ['all', 'created', 'processing', 'processed'],
        type: [
          'all',
          'payment_link',
          'refund',
          'irctc_refund',
          'irctc_settlement',
        ],
      },
      batch_fund_transfer: {
        type: ['all', 'settlement', 'payout', 'refund'],
        date: ['Date'],
      },
      billdesk: {
        AuthStatus: ['all', '0001', '0300', '0002', '0399', 'NA'],
        BankReferenceNo: ['Bank Reference No'],
        payment_id: ['Payment Id'],
        received: booleanList,
        RefStatus: ['Refund Status'],
        RefundId: ['Billdesk Refund Id'],
        TxnReferenceNo: ['Txn Reference No'],
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
          'Discover',
        ],
        status: statusList,
        vault: ['Vault'],
        vault_token: ['Vault Token'],
      },
      credits: {
        merchant_id: ['Merchant Id'],
        type: ['all', 'fee', 'amount'],
      },
      customer: {
        merchant_id: ['Merchant Id'],
        email: ['Email'],
        active: booleanList,
        contact: ['Contact'],
      },
      customer_balance: {
        merchant_id: ['Merchant ID'],
        customer_id: ['Customer ID'],
      },
      customer_transaction: {
        entity_id: ['Payment/Refund Id'],
        merchant_id: ['Merchant ID'],
        customer_id: ['Customer ID'],
        type: ['all', 'transfer', 'refund'],
      },
      cybersource: {
        payment_id: ['Payment ID'],
        received: booleanList,
        ref: ['Reference'],
        capture_ref: ['Capture Reference'],
      },
      ebs: {
        payment_id: ['Payment ID'],
      },
      fee_breakup: {
        transaction_id: ['Transaction Id'],
        pricing_rule_id: ['Pricing Rule Id'],
      },
      first_data: {
        payment_id: ['Payment ID'],
        action: ['Action'],
        received: ['Received'],
        refund_id: ['Refund ID'],
        gateway_payment_id: ['Gateway Payment ID'],
        tdate: ['Tdate'],
        caps_payment_id: ['Caps Payment ID'],
        gateway_transaction_id: ['Gateway Transaction ID'],
      },
      dispute: {
        merchant_id: ['Merchant ID'],
        payment_id: ['Payment ID'],
        status: ['all', 'open', 'under_review', 'won', 'lost', 'closed'],
        phase: [
          'all',
          'chargeback',
          'pre_arbitration',
          'arbitration',
          'retrieval',
          'fraud',
        ],
        amount: ['Amount'],
      },
      dispute_reason: {
        network: [
          'all',
          'RZP',
          'JCB',
          'Amex',
          'Visa',
          'RuPay',
          'Maestro',
          'Discover',
          'Unionpay',
          'Mastercard',
        ],
        code: ['Code'],
        description: ['Description'],
        gateway_code: ['Gateway Code'],
        gateway_description: ['Gateway Description'],
      },
      emi_plan: {
        bank: ['Bank'],
        network: ['Network'],
      },
      feature: {
        entity_id: ['Entity Id'],
        entity_type: ['Entity Type'],
        name: ['Name'],
      },
      file_store: {
        entity_id: ['Entity Id'],
        type: ['Type'],
        merchant_id: ['Merchant Id'],
      },
      fund_transfer_attempt: {
        batch_fund_transfer_id: ['Batch Fund Transfer Id'],
        source_type: ['all', 'settlement', 'payout', 'refund'],
        source_id: ['Source Id'],
        merchant_id: ['Merchant Id'],
        status: ['all', 'created', 'initiated', 'failed', 'processed'],
        utr: ['UTR'],
      },
      gateway_downtime: {
        method: methodList,
        gateway: gatewayList,
        bank: ['Bank'],
      },
      gateway_file: {
        type: ['all', 'emi', 'refund', 'combined'],
        status: [
          'all',
          'created',
          'file_generated',
          'file_sent',
          'failed',
          'acknowledged',
        ],
        target: gatewayFileTargetList,
      },
      hdfc: {
        auth: ['Auth Code'],
        gateway_transaction_id: ['Gateway Transaction Id'],
        payment_id: ['Payment Id'],
        refund_id: ['Refund Id'],
        received: booleanList,
        ref: ['Reference'],
      },
      iin: {
        emi: booleanList,
        otp_read: booleanList,
        iin: ['Iin'],
        international: booleanList,
        issuer: ['Issuer'],
        network: ['Network'],
        type: ['all', 'credit', 'debit', 'unknown'],
      },
      invoice: {
        batch_id: ['Batch Id'],
        payment_id: ['Payment Id'],
        receipt: ['Receipt'],
        user_id: ['User Id'],
        status: [
          'all',
          'draft',
          'issued',
          'partially_paid',
          'paid',
          'cancelled',
          'expired',
        ],
        type: ['all', 'ecod', 'link', 'invoice'],
        merchant_id: ['Merchant Id'],
        order_id: ['Order Id'],
        notes: ['Notes'],
        subscription_id: ['Subscription Id'],
        customer_name: ['Customer Name'],
        customer_email: ['Customer Email'],
        customer_contact: ['Customer Contact'],
      },
      item: {
        active: booleanList,
        type: ['Type'],
        merchant_id: ['Merchant Id'],
      },
      key: {
        merchant_id: ['Merchant Id'],
      },
      merchant: {
        activated: booleanList,
        amex: booleanList2,
        card: booleanList2,
        category: ['MCC Code'],
        category2: ['Category 2'],
        email: ['Email'],
        hold_funds: booleanList,
        international: booleanList,
        live: booleanList,
        mobikwik: booleanList2,
        paytm: booleanList2,
        payumoney: booleanList2,
        payzapp: booleanList2,
        olamoney: booleanList2,
        mpesa: booleanList2,
        upi: booleanList2,
        airtelmoney: booleanList2,
        freecharge: booleanList2,
        jiomoney: booleanList2,
        sbibuddy: booleanList2,
        pricing_plan_id: ['Pricing Plan Id'],
        parent_id: ['Marketplace Parent Id'],
        receipt_email_enabled: booleanList,
        fee_bearer: ['all', 'platform', 'customer'],
        fee_model: ['all', 'prepaid', 'postpaid'],
        risk_rating: ['all', 1, 2, 3, 4, 5],
      },
      merchant_detail: {},
      methods: {
        amex: booleanList,
        card: booleanList,
        emi: booleanList,
        mobikwik: booleanList,
        paytm: booleanList,
        payumoney: booleanList,
        payzapp: booleanList,
        mpesa: booleanList,
        olamoney: booleanList,
        upi: booleanList,
        airtelmoney: booleanList,
        freecharge: booleanList,
        jiomoney: booleanList,
        sbibuddy: booleanList,
        merchant_id: ['Merchant Id'],
      },
      merchant_invoice: {
        merchant_id: ['Merchant Id'],
        invoice_number: ['Invoice No.'],
        gstin: ['GSTIN'],
        month: ['Month'],
        year: ['Year'],
      },
      mobikwik: {
        payment_id: ['Payment Id'],
        received: booleanList,
      },
      netbanking: {
        bank_payment_id: ['Bank Reference Id'],
        caps_payment_id: ['Caps Payment Id'],
        int_payment_id: ['Int Payment Id'],
        payment_id: ['Payment Id'],
        received: booleanList,
      },
      offer: {
        merchant_id: ['Merchant Id'],
      },
      order: {
        account_number: ['Account Number'],
        authorized: booleanList,
        merchant_id: ['Merchant Id'],
        notes: ['Notes'],
        receipt: ['Receipt'],
        status: ['all', 'created', 'attempted', 'paid'],
      },
      payment_analytics: {
        checkout_id: ['Checkout Id'],
        payment_id: ['Payment Id'],
        merchant_id: ['Merchant Id'],
      },
      payment: {
        app_token: ['App Token'],
        amount: ['Amount'],
        bank: ['Bank Code'],
        card_id: ['Card Id'],
        customer_id: ['Customer Id'],
        global_customer_id: ['Global Customer Id'],
        email: ['Contact Email'],
        gateway: gatewayList,
        global_token_id: ['Global Token Id'],
        iin: ['Card IIN'],
        international: booleanList,
        invoice_id: ['Invoice Id'],
        last4: ['Card Last 4'],
        merchant_id: ['Merchant Id'],
        method: methodList,
        notes: ['Notes'],
        order_id: ['Order Id'],
        refund_status: ['all', 'null', 'partial', 'full'],
        save: booleanList,
        status: statusList,
        subscription_id: ['Subscription Id'],
        terminal_id: ['Terminal ID'],
        token_id: ['Token Id'],
        transfer_id: ['Transfer Id'],
        verified: ['all', 'null', 0, 1, 2],
        wallet: walletList,
      },
      payout: {
        merchant_id: ['Merchant Id'],
        customer_id: ['Customer Id'],
        destination: ['Bank Account Id'],
        method: ['all', 'fund_transfer'],
      },
      paytm: {
        payment_id: ['Payment Id'],
        received: booleanList,
      },
      plan: {
        interval: ['Interval'],
        item_id: ['Item_id'],
        merchant_id: ['Merchant Id'],
        period: ['Period'],
      },
      pricing: {
        plan_id: ['Plan Id'],
      },
      refund: {
        amount: ['Amount'],
        batch_id: ['Batch Id'],
        gateway: gatewayList,
        merchant_id: ['Merchant Id'],
        method: methodList,
        payment_id: ['Payment Id'],
        status: ['all', 'created', 'failed', 'processed'],
        transaction_id: ['Transaction Id'],
        notes: ['Notes'],
      },
      report: {
        merchant_id: ['Merchant Id'],
        type: [
          'all',
          'merchant',
          'order',
          'payment',
          'refund',
          'reversal',
          'settlement',
          'transaction',
        ],
      },
      reversal: {
        merchant_id: ['Merchant Id'],
        transfer_id: ['Transfer Id'],
      },
      risk: {
        fraud_type: ['suspected', 'confirmed'],
        source: ['bank', 'gateway', 'maxmind', 'manual', 'internal'],
        merchant_id: ['Merchant Id'],
        payment_id: ['Payment Id'],
      },
      settlement: {
        batch_fund_transfer_id: ['Batch Fund Transfer Id'],
        merchant_id: ['Merchant Id'],
        status: ['all', 'created', 'failed', 'processed'],
        transaction_id: ['Transaction Id'],
        utr: ['UTR'],
      },
      settlement_details: {
        merchant_id: ['Merchant Id'],
        settlement_id: ['Settlement Id'],
      },
      subscription: {
        auth_attempts: ['Auth Attempts'],
        customer_email: ['Customer Email'],
        customer_id: ['Customer Id'],
        error_status: ['Error Status'],
        merchant_id: ['Merchant Id'],
        notes: ['Notes'],
        plan_id: ['Plan Id'],
        schedule_id: ['Schedule Id'],
        status: [
          'all',
          'created',
          'authenticated',
          'active',
          'pending',
          'halted',
          'cancelled',
          'completed',
          'expired',
        ],
        token_id: ['Token Id'],
      },
      terminal: {
        enabled: booleanList,
        gateway: gatewayList,
        category: ['Category'],
        merchant_id: ['Merchant Id'],
        shared: booleanList,
        gateway_merchant_id: ['Gateway Merchant Id'],
        gateway_terminal_id: ['Gateway Terminal Id'],
        network_category: ['Network Category'],
        gateway_acquirer: ['all', 'axis', 'hdfc', 'icic'],
        emi: booleanList,
      },
      transaction: {
        entity_id: ['Payment/Refund/Settlement Id'],
        merchant_id: ['Merchant Id'],
        reconciled: booleanList,
        settled: booleanList,
        on_hold: booleanList,
        settlement_id: ['Settlement Id'],
        type: [
          'all',
          'payment',
          'refund',
          'settlement',
          'adjustment',
          'transfer',
          'reversal',
          'payout',
        ],
      },
      transfer: {
        source: ['Source Payment/Merchant Id'],
        recipient: ['Recipient Merchant/Customer Id'],
        merchant_id: ['Merchant Id'],
      },
      token: {
        bank: ['Bank Code'],
        card_id: ['Card Id'],
        customer_id: ['Customer Id'],
        merchant_id: ['Merchant Id'],
        method: methodList,
        terminal_id: ['Terminal Id'],
        token: ['Token'],
        wallet: walletList,
      },
      upi: {
        payment_id: ['Payment Id'],
        bank: upiBankList,
      },
      user: {
        email: ['Email'],
      },
      virtual_account: {
        merchant_id: ['Merchant ID'],
        status: ['all', 'active', 'closed', 'paid'],
        customer_id: ['Customer ID'],
      },
      wallet: {
        payment_id: ['Payment Id'],
        gateway_payment_id: ['Gateway Payment Id'],
        wallet: walletList,
      },
      webhook: {
        merchant_id: ['Merchant Id'],
      },
      schedule: {
        merchant_id: ['Merchant Id'],
      },
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

    $scope.$watch('mode + entity_type + from + to', function() {
      $state.go(
        'app.entities',
        {
          mode: $scope.mode,
          type: $scope.entity_type,
        },
        { notify: false }
      );
      $scope.showTable();
    });
    $scope.notSorted = function(obj) {
      if (!obj) {
        return [];
      }
      var data = Object.keys(obj);
      data.splice(-1, 1);
      return data;
    };
    $scope.displayFilter = function(val) {
      var labels = {
        0: 'no',
        1: 'yes',
        true: 'yes',
        false: 'no',
        '0001': 'BillDesk Cancel',
        '0300': 'Success',
        '0002': 'Bank Pending',
        '0399': 'Bank Cancel Auth Error',
        NA: 'Invalid Input',
      };
      if (val in labels) {
        return labels[val];
      } else {
        return val;
      }
    };
    $scope.next = function() {
      clear('id');
      $scope.entity.skip += $scope.count;
      $scope.generateTable();
    };
    $scope.prev = function() {
      clear('id');
      $scope.entity.skip -= $scope.count;
      $scope.generateTable();
    };

    $scope.getEntityListForUI = function() {
      var uiEntities = {};
      for (var entity in $scope.availableFilters) {
        uiEntities[entity] = entity.replace('_', ' ');
      }

      return uiEntities;
    };
    $scope.search = function() {
      if (!$scope.entity.id) {
        return;
      }
      clear('skip');

      var routeName = 'admin_fetch_entity_by_id';
      if ($scope.entity_type === 'terminal') {
        routeName = 'admin_fetch_terminal_by_id';
      }
      var data = {
        route_name: routeName,
        url_params: {
          '{type}': $scope.entity_type,
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
            var entity = data.data.entity;
            var stateArray = {
              payment: 'app.payments',
              merchant: 'app.merchants.detail',
            };
            var state = 'app.entitiesdetail';
            if (entity in stateArray) {
              state = stateArray[entity];
            }
            $state.go(state, {
              mode: $scope.mode,
              id: data.data.id,
              type: entity,
            });
          } else {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function(res) {
          $scope.alerts.addAlert('danger', res ? res : null, true);
        });
    };
    if ($stateParams.type && $stateParams.id) {
      $scope.entity.id = $stateParams.id;
      $scope.entity_type = $stateParams.type;
      $scope.search();
    }
    function clear(field) {
      if (field == 'id') $scope.entity.id = '';
      else $scope.entity.skip = 0;
    }
    $scope.showTable = function() {
      clear('id');
      clear('skip');
      $scope.generateTable();
    };
    $scope.getState = getState;
    function generateQueryParams(count, skip, entity, filters, from, to) {
      var query = {
        count: count,
        skip: skip,
      };
      if (from !== 0) {
        query.from = from;
      }
      if (to !== 0) {
        query.to = to;
      }
      // We send the methods param in a JSON encoded format
      if (entity === 'merchant') {
        var methods = {},
          validMethods = [
            'amex',
            'card',
            'emi',
            'paytm',
            'mobikwik',
            'payzapp',
            'payumoney',
            'upi',
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
    $scope.generateTable = function(csv) {
      if (!$scope.entity_type) {
        console.log('Error: No Entity Type Specified');
        return;
      }
      var query = generateQueryParams(
        $scope.count,
        $scope.entity.skip,
        $scope.entity_type,
        $scope.filters,
        $scope.from,
        $scope.to
      );
      if (csv) {
        var url =
          '/admin/' + $scope.mode + '/fetchentity/' + $scope.entity_type;
        window.open(url + '/csv?' + $.param(query));
        return;
      }
      var data = {
        route_name: 'admin_fetch_entity_multiple',
        url_params: {
          '{type}': $scope.entity_type,
        },
        mode: $scope.mode,
        query_params: query,
      };
      var request = $http.get('/admin/generic', {
        params: data,
      });
      request
        .success(function(data) {
          $scope.alerts.resetAlerts();
          if (data.success) {
            $scope.headings = [];
            if (Array.isArray(data.data.items) && data.data.items.length) {
              $scope.headings = Object.keys(data.data.items[0]);
            }
            $scope.entity.items = data.data.items;

            $scope.entity.count = parseInt(data.data.count);
            $scope.entity.countStart = $scope.entity.skip + 1;
            if (data.data.count === 0)
              $scope.entity.countEnd = $scope.entity.countStart;
            else
              $scope.entity.countEnd =
                $scope.entity.countStart + $scope.entity.count - 1;
            $scope.allowPrev = $scope.entity.countStart != 1;
            $scope.allowNext = $scope.entity.count >= $scope.count;
          } else {
            if (data.errors) {
              angular.forEach(data.errors, function(value) {
                $scope.alerts.addAlert('danger', value);
              });
            } else {
              $scope.alerts.addAlert('danger', null, true);
            }
          }
        })
        .error(function(res) {
          $scope.alerts.addAlert('danger', res ? res : null, true);
        });
    };
  },
]);
