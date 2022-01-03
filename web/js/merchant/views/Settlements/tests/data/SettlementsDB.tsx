const settlementsInfo = {
  resourceIdField: 'id',
  id: 'setl_H3tqdiL5v1DgVM',
  entity: 'settlement',
  amount: 10982272,
  status: 'created',
  fees: 106828,
  tax: 16294,
  utr: null,
  created_at: 1619516570,
};

const settleBreakupDetails = {
  loading: false,
  items: [
    {
      component: 'payment_domestic',
      amount: 10989100,
      count: 21,
      type: 'credit',
      fee: 90534,
      tax: 16294,
      settled_amount: 10882272,
    },
    {
      resourceIdField: 'id',
      component: 'adjustment',
      amount: 100000,
      count: 1,
      type: 'credit',
      fee: 0,
      tax: 0,
      resourceUrl: 'settlements',
      amountInINR: '1000.00',
      settled_amount: 100000,
    },
    {
      resourceIdField: 'id',
      component: 'refund',
      amount: 1000,
      count: 1,
      type: 'debit',
      fee: 0,
      tax: 0,
      resourceUrl: 'settlements',
      amountInINR: '10.00',
      settled_amount: -1000,
    },
  ],
  error: null,
  isBreakupNew: true,
};

const settlementTabBreakupDetails = settleBreakupDetails;

const settlementsListData = [
  {
    id: 'pay_FJu3t7nbtkvtJU',
    amount: 10000,
    fee: 0,
    tax: 0,
    created_at: 1595936592,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FPLZieUZheoox5',
    amount: 20000,
    fee: 0,
    tax: 0,
    created_at: 1597125184,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FTMtN1iCWQlngz',
    amount: 10000,
    fee: 0,
    tax: 0,
    created_at: 1598003182,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FTMzpROF91z4Md',
    amount: 10000,
    fee: 0,
    tax: 0,
    created_at: 1598003549,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FTNHOrKvTsxW9e',
    amount: 10000,
    fee: 0,
    tax: 0,
    created_at: 1598004547,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FXKyW7aumCnYd3',
    amount: 6402400,
    fee: 0,
    tax: 0,
    created_at: 1598869792,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FXL28lnU1EDFBJ',
    amount: 100,
    fee: 2,
    tax: 0,
    created_at: 1598869997,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FXL2AnRjYGUpxu',
    amount: 100,
    fee: 2,
    tax: 0,
    created_at: 1598869999,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FXL6NielqOhvlL',
    amount: 1790400,
    fee: 42254,
    tax: 6446,
    created_at: 1598870238,
    international: false,
    status: 'captured',
  },
  {
    id: 'pay_FXLzHyIIzsCKZ8',
    amount: 360500,
    fee: 8508,
    tax: 1298,
    created_at: 1598873357,
    international: false,
    status: 'captured',
  },
];

const settlementsListRefundData = [
  {
    id: 'ref_FJu3t7nbtkvtJU',
    amount: 10000,
    fee: 0,
    tax: 0,
    created_at: 1595936592,
    international: false,
    status: 'captured',
  },
];

const holidaysList = {
  2022: [
    {
      date: '26/01/2022',
      description: 'Republic Day',
    },
    {
      date: '19/02/2022',
      description: 'Chhatrapati Shivaji Maharaj Jayanti',
    },
    {
      date: '25/12/2022',
      description: 'Christmas',
    },
  ],
};

const schedule = [
  {
    method: 'netbanking',
    type: 'settlement',
    name: 'test',
    period: 'hourly',
    interval: 24,
    anchor: null,
    hour: [0],
    delay: 24,
    international: 0,
    is_early_settlement_schedule: false,
  },
  {
    method: null,
    type: 'settlement',
    name: 'Hourly Early Settlement',
    period: 'hourly',
    interval: 1,
    anchor: null,
    hour: [0],
    delay: 0,
    international: 0,
    is_early_settlement_schedule: false,
  },
  {
    method: 'upi',
    type: 'settlement',
    name: 'Hourly Early Settlement',
    period: 'hourly',
    interval: 1,
    anchor: null,
    hour: [12],
    delay: 0,
    international: 0,
    is_early_settlement_schedule: true,
  },
];

const scheduledPricing = {
  percent_rate: 15,
  fixed_rate: 0,
  fee_bearer: 'customer',
};

export {
  settlementsInfo,
  settleBreakupDetails,
  settlementTabBreakupDetails,
  settlementsListData,
  settlementsListRefundData,
  holidaysList,
  scheduledPricing,
  schedule,
};
