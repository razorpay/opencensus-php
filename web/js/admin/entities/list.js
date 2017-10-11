import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { SelectField, SwitchField } from 'ui/Field';
import Collection from 'util/collection';
import { adminFetch } from 'util/fetch';

export default class EntityList extends Component {
  collection = new Collection({
    fetchRoute: 'pricing_get_merchant_plans',
    fetchFn: adminFetch,
  });

  state = {
    entity_type: 'payment',
  };

  onSubmit = filters => this.collection.setFilters(filters);

  changeFilters({ target }) {
    this.setState({
      entity_type: target.value,
    });
  }

  render() {
    let entity_type = this.state.entity_type;
    let filters = entities[entity_type];

    return (
      <div class="list-container">
        <div class="box">
          <header>Entities</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <SelectField
              name="entity_type"
              label="Entity"
              onChange={::this.changeFilters}
              defaultValue={this.state.entity_type}
            >
              {Object.keys(entities).map((entity_type, index) => (
                <option key={index} value={entity_type}>
                  {entity_type}
                </option>
              ))}
            </SelectField>
            <SwitchField
              disabledValue="test"
              value="live"
              mode="mode"
              label="Live Mode"
            />
            <Field
              label="Count"
              class="small"
              name="count"
              type="number"
              defaultValue="20"
              min="10"
              max="1000"
              step="10"
            />
            <Field label="From" type="datetime-local" name="from" />
            <Field label="To" type="datetime-local" name="to" />
            <Field label="ID" name="entity.id" />

            <div class="more-filters-following" />
            {filters &&
              Object.keys(filters).map((filterName, index) => {
                let filterValue = filters[filterName];
                if (typeof filterValue === 'string')
                  return (
                    <Field key={index} label={filterValue} name={filterName} />
                  );
                return (
                  <SelectField label={filterName} name={filterName} key={index}>
                    {filterValue instanceof Array &&
                      filterValue.map((o, i) => (
                        <option value={o} key={i}>
                          {o}
                        </option>
                      ))}
                  </SelectField>
                );
              })}
            <button>Go</button>
          </Form>
        </div>
      </div>
    );
  }
}

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

// array = <select>
// string = <input>
var entities = {
  addon: {
    deleted: booleanList,
    invoice_id: 'Invoice Id',
    merchant_id: 'Merchant Id',
    subscription_id: 'Subscription Id',
  },
  adjustment: { merchant_id: 'Merchant Id' },
  amex: {
    payment_id: 'Payment Id',
    received: booleanList,
    vpc_ReceiptNo: 'Receipt Number',
  },
  app_token: {
    customer_id: 'Customer Id',
    device_token: 'Device Token',
    merchant_id: 'Merchant Id',
  },
  axis_genius: {
    payment_id: 'Payment Id',
    received: booleanList,
    vpc_ReceiptNo: 'Receipt Number',
  },
  axis_migs: {
    payment_id: 'Payment Id',
    received: booleanList,
    vpc_ReceiptNo: 'Receipt No',
    vpc_ShopTransactionNo: 'Shop Transaction No',
    vpc_TransactionNo: 'Transaction No',
    vpc_TxnResponseCode: 'Txn Response Code',
    vpc_3DSstatus: ['all', 'Y', 'N', 'U', 'A'],
  },
  balance: {},
  bank_account: {
    deleted: booleanList,
    entity_id: 'Entity Id',
    merchant_id: 'Merchant Id',
    type: ['all', 'customer', 'merchant'],
  },
  bank_transfer: {
    merchant_id: 'Merchant Id',
    payment_id: 'Payment ID',
    utr: 'UTR',
    virtual_account_id: 'Virtual Account ID',
    mode: ['all', 'neft', 'rtgs', 'ift', 'imps'],
    payer_account: 'Payer Account',
    payer_ifsc: 'Payer IFSC',
    payee_account: 'Payee Account',
    payee_ifsc: 'Payee IFSC',
    amount: 'Amount',
  },
  batch: {
    merchant_id: 'Merchant Id',
    status: ['all', 'created', 'processing', 'processed'],
    type: ['all', 'payment_link', 'refund', 'irctc_refund', 'irctc_settlement'],
  },
  batch_fund_transfer: {
    type: ['all', 'settlement', 'payout'],
    date: 'Date',
  },
  billdesk: {
    AuthStatus: ['all', '0001', '0300', '0002', '0399', 'NA'],
    BankReferenceNo: 'Bank Reference No',
    payment_id: 'Payment Id',
    received: booleanList,
    RefStatus: 'Refund Status',
    RefundId: 'Billdesk Refund Id',
    TxnReferenceNo: 'Txn Reference No',
  },
  card: {
    global_card_id: 'Global Card Id',
    iin: 'IIN',
    international: booleanList,
    last4: 'last4',
    merchant_id: 'Merchant Id',
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
    vault: 'Vault',
    vault_token: 'Vault Token',
  },
  credits: {
    merchant_id: 'Merchant Id',
    type: ['all', 'fee', 'amount'],
  },
  customer: {
    merchant_id: 'Merchant Id',
    email: 'Email',
    active: booleanList,
    contact: 'Contact',
  },
  customer_balance: {
    merchant_id: 'Merchant ID',
    customer_id: 'Customer ID',
  },
  customer_transaction: {
    entity_id: 'Payment/Refund Id',
    merchant_id: 'Merchant ID',
    customer_id: 'Customer ID',
    type: ['all', 'transfer', 'refund'],
  },
  cybersource: {
    payment_id: 'Payment ID',
    received: booleanList,
    ref: 'Reference',
    capture_ref: 'Capture Reference',
  },
  ebs: {
    payment_id: 'Payment ID',
  },
  fee_breakup: {
    transaction_id: 'Transaction Id',
    pricing_rule_id: 'Pricing Rule Id',
  },
  first_data: {
    payment_id: 'Payment ID',
    action: 'Action',
    received: 'Received',
    refund_id: 'Refund ID',
    gateway_payment_id: 'Gateway Payment ID',
    tdate: 'Tdate',
    caps_payment_id: 'Caps Payment ID',
    gateway_transaction_id: 'Gateway Transaction ID',
  },
  dispute: {
    merchant_id: 'Merchant ID',
    payment_id: 'Payment ID',
    status: ['open', 'under_review', 'won', 'lost'],
    phase: ['chargeback', 'pre_arbitration', 'arbitration'],
    amount: 'Amount',
  },
  emi_plan: {
    bank: 'Bank',
    network: 'Network',
  },
  feature: {
    entity_id: 'Entity Id',
    entity_type: 'Entity Type',
    name: 'Name',
  },
  file_store: {
    entity_id: 'Entity Id',
    type: 'Type',
  },
  fund_transfer_attempt: {
    batch_fund_transfer_id: 'Batch Fund Transfer Id',
    source_type: ['all', 'settlement', 'payout'],
    source_id: 'Source Id',
    merchant_id: 'Merchant Id',
    status: ['all', 'created', 'initiated', 'failed', 'processed'],
    utr: 'UTR',
  },
  gateway_downtime: {
    method: methodList,
    gateway: gatewayList,
    bank: 'Bank',
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
    auth: 'Auth Code',
    gateway_transaction_id: 'Gateway Transaction Id',
    payment_id: 'Payment Id',
    refund_id: 'Refund Id',
    received: booleanList,
    ref: 'Reference',
  },
  iin: {
    emi: booleanList,
    otp_read: booleanList,
    iin: 'Iin',
    international: booleanList,
    issuer: 'Issuer',
    network: 'Network',
    type: ['all', 'credit', 'debit', 'unknown'],
  },
  invoice: {
    batch_id: 'Batch Id',
    payment_id: 'Payment Id',
    receipt: 'Receipt',
    user_id: 'User Id',
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
    merchant_id: 'Merchant Id',
    order_id: 'Order Id',
    notes: 'Notes',
    subscription_id: 'Subscription Id',
    customer_name: 'Customer Name',
    customer_email: 'Customer Email',
    customer_contact: 'Customer Contact',
  },
  item: {
    active: booleanList,
    type: 'Type',
    merchant_id: 'Merchant Id',
  },
  key: {
    merchant_id: 'Merchant Id',
  },
  merchant: {
    activated: booleanList,
    amex: booleanList2,
    card: booleanList2,
    category: 'MCC Code',
    category2: 'Category 2',
    email: 'Email',
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
    pricing_plan_id: 'Pricing Plan Id',
    parent_id: 'Marketplace Parent Id',
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
    merchant_id: 'Merchant Id',
  },
  merchant_invoice: {
    merchant_id: 'Merchant Id',
    invoice_number: ['Invoice No.'],
    gstin: 'GSTIN',
    month: 'Month',
    year: 'Year',
  },
  mobikwik: {
    payment_id: 'Payment Id',
    received: booleanList,
  },
  netbanking: {
    bank_payment_id: 'Bank Reference Id',
    caps_payment_id: 'Caps Payment Id',
    int_payment_id: 'Int Payment Id',
    payment_id: 'Payment Id',
    received: booleanList,
  },
  offer: {
    merchant_id: 'Merchant Id',
  },
  order: {
    account_number: 'Account Number',
    authorized: booleanList,
    merchant_id: 'Merchant Id',
    notes: 'Notes',
    receipt: 'Receipt',
    status: ['all', 'created', 'attempted', 'paid'],
  },
  payment_analytics: {
    checkout_id: 'Checkout Id',
    payment_id: 'Payment Id',
    merchant_id: 'Merchant Id',
  },
  payment: {
    app_token: 'App Token',
    amount: 'Amount',
    bank: 'Bank Code',
    card_id: 'Card Id',
    customer_id: 'Customer Id',
    global_customer_id: 'Global Customer Id',
    email: 'Contact Email',
    gateway: gatewayList,
    global_token_id: 'Global Token Id',
    iin: 'Card IIN',
    international: booleanList,
    invoice_id: 'Invoice Id',
    last4: 'Card Last 4',
    merchant_id: 'Merchant Id',
    method: methodList,
    notes: 'Notes',
    order_id: 'Order Id',
    refund_status: ['all', 'null', 'partial', 'full'],
    save: booleanList,
    status: statusList,
    subscription_id: 'Subscription Id',
    terminal_id: 'Terminal ID',
    token_id: 'Token Id',
    transfer_id: 'Transfer Id',
    verified: ['all', 'null', 0, 1, 2],
    wallet: walletList,
  },
  payout: {
    merchant_id: 'Merchant Id',
    customer_id: 'Customer Id',
    destination: 'Bank Account Id',
    method: ['all', 'fund_transfer'],
  },
  paytm: {
    payment_id: 'Payment Id',
    received: booleanList,
  },
  plan: {
    interval: 'Interval',
    item_id: 'Item_id',
    merchant_id: 'Merchant Id',
    period: 'Period',
  },
  pricing: {
    plan_id: 'Plan Id',
  },
  refund: {
    amount: 'Amount',
    batch_id: 'Batch Id',
    gateway: gatewayList,
    merchant_id: 'Merchant Id',
    payment_id: 'Payment Id',
    status: ['all', 'created', 'failed', 'processed'],
    transaction_id: 'Transaction Id',
    notes: 'Notes',
  },
  reversal: {
    merchant_id: 'Merchant Id',
    transfer_id: 'Transfer Id',
  },
  risk: {
    fraud_type: ['suspected', 'confirmed'],
    source: ['bank', 'gateway', 'maxmind', 'manual', 'internal'],
    merchant_id: 'Merchant Id',
    payment_id: 'Payment Id',
  },
  settlement: {
    batch_fund_transfer_id: 'Batch Fund Transfer Id',
    merchant_id: 'Merchant Id',
    status: ['all', 'created', 'failed', 'processed'],
    transaction_id: 'Transaction Id',
    utr: 'UTR',
  },
  settlement_details: {
    merchant_id: 'Merchant Id',
    settlement_id: 'Settlement Id',
  },
  subscription: {
    auth_attempts: 'Auth Attempts',
    customer_email: 'Customer Email',
    customer_id: 'Customer Id',
    error_status: 'Error Status',
    merchant_id: 'Merchant Id',
    notes: 'Notes',
    plan_id: 'Plan Id',
    schedule_id: 'Schedule Id',
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
    token_id: 'Token Id',
  },
  terminal: {
    enabled: booleanList,
    gateway: gatewayList,
    category: 'Category',
    merchant_id: 'Merchant Id',
    shared: booleanList,
    gateway_merchant_id: 'Gateway Merchant Id',
    gateway_terminal_id: 'Gateway Terminal Id',
    network_category: 'Network Category',
    gateway_acquirer: ['all', 'axis', 'hdfc', 'icic'],
    emi: booleanList,
  },
  transaction: {
    entity_id: 'Payment/Refund/Settlement Id',
    merchant_id: 'Merchant Id',
    reconciled: booleanList,
    settled: booleanList,
    on_hold: booleanList,
    settlement_id: 'Settlement Id',
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
    source: 'Source Payment/Merchant Id',
    recipient: 'Recipient Merchant/Customer Id',
    merchant_id: 'Merchant Id',
  },
  token: {
    bank: 'Bank Code',
    card_id: 'Card Id',
    customer_id: 'Customer Id',
    merchant_id: 'Merchant Id',
    method: methodList,
    terminal_id: 'Terminal Id',
    token: 'Token',
    wallet: walletList,
  },
  upi: {
    payment_id: 'Payment Id',
    bank: upiBankList,
  },
  user: {
    email: 'Email',
  },
  virtual_account: {
    merchant_id: 'Merchant ID',
    status: ['all', 'active', 'closed', 'paid'],
    customer_id: 'Customer ID',
  },
  wallet: {
    payment_id: 'Payment Id',
    wallet: walletList,
  },
  webhook: {
    merchant_id: 'Merchant Id',
  },
  schedule: {
    merchant_id: 'Merchant Id',
  },
};
