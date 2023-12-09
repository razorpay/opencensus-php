export const refundTransaction = {
  id: 'txn_KP1XD8Q5FzbGfE',
  entity: 'transaction',
  entity_id: 'rfnd_KP1XCdCCFnRqyj',
  type: 'refund',
  debit: 931860,
  credit: 0,
  amount: 931860,
  currency: 'INR',
  fee: 0,
  tax: 0,
  on_hold: false,
  settled: false,
  created_at: 1664740041,
  settled_at: 1665081000,
  settlement_id: null,
  posted_at: null,
  credit_type: 'default',
  settlement: null,
};

export const refund = {
  resourceIdField: 'id',
  id: 'rfnd_KP1XCdCCFnRqyj',
  entity: 'refund',
  amount: 931860,
  fees: 244,
  tax: 33,
  currency: 'INR',
  payment_id: 'pay_KP1U0tVAkqMqpw',
  notes: [],
  receipt: null,
  optimizer_provider: 'Razorpay',
  acquirer_data: {
    arn: null,
  },
  created_at: 1664740041,
  batch_id: null,
  status: 'processed',
  speed_processed: null,
  speed_requested: 'normal',
  processed_at: 1664740041,
  resourceUrl: 'refunds',
  amountInINR: '9318.60',
  gateway_data: {
    refund_code: 'ERROR_CODE',
    refund_message: 'Sample message',
  },
  analyticsPayload: () => {},
};

export const refundFilterInitState = {
  session: {
    user: {
      isOptimizerEnabled: true,
      isSingleReconEnabled: true,
    },
  },
  config: {
    config: {
      rs_filter: true,
    },
  },
  navigator: {
    terminalProviders: [
      {
        Gateway: 'razorpay',
      },
    ],
  },
};

export const RefundMilestones = {
  Processing: {
    Normal: {
      status: 'processing',
      mode: `Normal Refund`,
    },
    Instant: {
      status: 'processing',
      mode: `Instant Refund`,
    },
  },
  Processed: {
    Normal: {
      status: 'processed',
      mode: 'Normal Refund',
    },
    Instant: {
      status: 'processed',
      mode: 'Instant Refund',
    },
  },
  Failed: {
    Normal: {
      status: 'failed',
      mode: 'Normal Refund',
    },
    Instant: {
      status: 'failed',
      mode: 'Instant Refund',
      infoText:
        'Instant Refund was unsuccessful, the fee & amount for instant refund has been reversed.',
    },
  },
  Text: {
    SpeedUpdatedToNormal: {
      text: 'Refund speed updated to Normal',
    },
    InstantRefundFailed: {
      text: 'Instant Refund for this payment has failed due to a bank issue and normal refund',
    },
    RefundInitiated: {
      text: 'The refund has been initiated. Once the refund is completed, the status of the refund will change to',
    },
  },
};

export const MockListPayload = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collection',
    count: 1,
    has_more: true,
    items: [refund],
  },
};
