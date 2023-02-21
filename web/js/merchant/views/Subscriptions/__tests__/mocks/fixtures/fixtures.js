import { rest } from 'msw';

const paymentsRes = {
  entity: 'collection',
  count: 1,
  has_more: true,
  items: [
    {
      id: 'pay_L5g0SxS5jHBWNt',
      entity: 'payment',
      amount: 100,
      currency: 'INR',
      base_amount: 100,
      status: 'captured',
      order_id: 'order_L5fyvZVSogMg6W',
      invoice_id: 'inv_L5fyvBUFAGdhDS',
      international: false,
      method: 'card',
      amount_refunded: 0,
      amount_transferred: 0,
      refund_status: null,
      captured: true,
      description: 'Invoice #inv_L5fyvBUFAGdhDS',
      card_id: 'card_L5g0T272SeSfjp',
      card: {
        id: 'card_L5g0T272SeSfjp',
        entity: 'card',
        name: '',
        last4: '0326',
        network: 'MasterCard',
        type: 'debit',
        issuer: 'CNRB',
        international: false,
        emi: false,
        sub_type: 'consumer',
        token_iin: null,
      },
      bank: null,
      wallet: null,
      vpa: null,
      email: 'satanick.dutta@razorpay.com',
      contact: '+918407983457',
      customer_id: 'cust_L5fyv7JlHi0sqB',
      token_id: 'token_L5g0TDQR0uEkjB',
      notes: [],
      fee: 0,
      tax: 0,
      error_code: null,
      error_description: null,
      error_source: null,
      error_step: null,
      error_reason: null,
      acquirer_data: {
        auth_code: '686189',
        arn: '00236303019035786283522',
        rrn: '003578628352',
      },
      created_at: 1674052853,
    },
  ],
};

const batchRes = {
  entity: 'collection',
  count: 25,
  has_more: true,
  items: [
    {
      created_at: 1624529654,
      updated_at: 1624529662,
      id: 'batch_HQrMnXldDbfKzV',
      entity_id: '8TgNt9DVrJB0bl',
      name: 'sample_recurring_payments - sample_recurring_payments',
      batch_type_id: 'recurring_charge',
      mode: 'test',
      creator_id: 'CQb3tob3Y9nQuv',
      creator_type: 'user',
      is_scheduled: false,
      upload_count: 0,
      processed_count: 1,
      failure_count: 1,
      total_count: 1,
      success_count: 0,
      attempts: 0,
      status: 'processed',
      amount: 100,
      processed_amount: 0,
      schedule_time: null,
      type: 'recurring_charge',
      entity: 'batch',
      config: {
        amount_as_rupee: true,
      },
    },
  ],
};

const batchResWithAuthLink = {
  ...batchRes,
  items: [
    {
      ...batchRes.items[0],
      type: 'auth_link',
      batch_type_id: 'auth_link',
    },
  ],
};

const batchDetailsRes = {
  created_at: 1672810958,
  updated_at: 1672810959,
  id: 'batch_KzzMEGESNoIhzY',
  entity_id: '8TgNt9DVrJB0bl',
  name: 'auth_link',
  batch_type_id: 'auth_link',
  mode: 'test',
  creator_id: 'HoWFJXWsZq6E2w',
  creator_type: 'user',
  is_scheduled: false,
  upload_count: 0,
  processed_count: 1,
  failure_count: 0,
  total_count: 1,
  success_count: 1,
  attempts: 0,
  status: 'processed',
  amount: 0,
  processed_amount: 0,
  schedule_time: null,
  type: 'auth_link',
  entity: 'batch',
  config: null,
};

const plansRes = {
  entity: 'collection',
  count: 1,
  items: [
    {
      id: 'plan_Kovo2tP4eWkjlf',
      entity: 'plan',
      interval: 1,
      period: 'yearly',
      item: {
        id: 'item_Kovo2t6z9d8tsY',
        active: true,
        name: 'test-plan',
        description: 'test',
        amount: 100,
        unit_amount: 100,
        currency: 'INR',
        type: 'plan',
        unit: null,
        tax_inclusive: false,
        hsn_code: null,
        sac_code: null,
        tax_rate: null,
        tax_id: null,
        tax_group_id: null,
        created_at: 1670396710,
        updated_at: 1670396710,
      },
      notes: [],
      created_at: 1670396710,
    },
  ],
};

const subscriptionsRes = {
  entity: 'collection',
  count: 1,
  items: [
    {
      id: 'sub_Kp2SiJihD0j5FG',
      entity: 'subscription',
      plan_id: 'plan_Kovo2tP4eWkjlf',
      customer_id: 'cust_123',
      status: 'created',
      type: 1,
      current_start: null,
      current_end: null,
      ended_at: null,
      quantity: 1,
      notes: [],
      charge_at: null,
      start_at: null,
      end_at: null,
      auth_attempts: 0,
      total_count: 2,
      paid_count: 0,
      customer_notify: true,
      created_at: 1670420150,
      expire_by: null,
      short_url: 'https://rzp.io/i/gfRZeisObn',
      has_scheduled_changes: false,
      change_scheduled_at: null,
      source: 'dashboard',
      payment_method: null,
      offer_id: null,
      remaining_count: 1,
    },
  ],
};

const subscriptionsOverviewRes = {
  subscriptions_active: 0,
  subscriptions_failed: 0,
  subscriptions_halted: 0,
  subscriptions_completing: 0,
  cards_expiring: 0,
};
export const fetchRecurringPayments = () => {
  const url = '*/merchant/api/*/payments';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: paymentsRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchBatches = (fetchOnlyAuthLinks = false) => {
  const url = '*/merchant/api/*/batches';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: fetchOnlyAuthLinks ? batchResWithAuthLink : batchRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchBatchDetails = () => {
  const url = '*/merchant/api/*/batches/*';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: batchDetailsRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchPlans = () => {
  const url = '*/merchant/api/*/plans';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: plansRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchSubscriptions = () => {
  const url = '*/merchant/api/*/subscriptions';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: subscriptionsRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchSubscriptionsOverview = () => {
  const url = '*/merchant/api/*/subscriptions/overview';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: subscriptionsOverviewRes,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchSubscriptionsOverviewError = () => {
  const url = '*/merchant/api/*/subscriptions/overview';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: false,
        errors: [],
      }),
      ctx.delay(50),
    );
  });
};
