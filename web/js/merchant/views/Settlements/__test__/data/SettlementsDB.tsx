const settlementsInfo = {
  resourceIdField: 'id',
  id: 'setl_H3tqdiL5v1DgVM',
  entity: 'settlement',
  amount: 10982272,
  status: 'created',
  fees: 106828,
  tax: 16294,
  utr: 'test-utr',
  created_at: 1619516570,
  optimizer_provider: 'PayU',
};

const settleBreakupDetails = {
  loading: false,
  items: [
    {
      component: 'adjustment',
      amount: 50300,
      count: 16,
      type: 'debit',
      fee: 0,
      tax: 0,
    },
    {
      component: 'payment_domestic',
      amount: 34647940,
      count: 204,
      type: 'credit',
      fee: 678291,
      tax: 122088,
    },
    {
      component: 'refund_domestic',
      amount: 21511,
      count: 8,
      type: 'debit',
      fee: 0,
      tax: 0,
    },
    {
      component: 'reversal',
      amount: 101200,
      count: 5,
      type: 'credit',
      fee: 0,
      tax: 0,
    },
    {
      component: 'transfer',
      amount: 185539,
      count: 45,
      type: 'debit',
      fee: 0,
      tax: 0,
    },
  ],
  error: null,
  isBreakupNew: true,
};

const settleBreakupDetailsWithNewBreakup = {
  loading: false,
  items: settleBreakupDetails.items.map((item) => ({ ...item, settled_amount: item.amount })),
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
      date: '01/03/2022',
      description: 'Mahashivratri',
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

const settlementMockRes = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collection',
    count: 5,
    has_more: true,
    items: [
      {
        id: 'setl_MpTrOLuUiK55Ce',
        entity: 'settlement',
        amount: 194,
        status: 'processed',
        fees: 0,
        tax: 0,
        utr: null,
        created_at: 1697590809,
      },
      {
        id: 'setl_Mp8w6M7e4lX6rL',
        entity: 'settlement',
        amount: 0,
        status: 'processed',
        fees: 0,
        tax: 0,
        utr: null,
        created_at: 1697517122,
      },
      {
        id: 'setl_MomvZtw5mEl5yx',
        entity: 'settlement',
        amount: 178,
        status: 'processed',
        fees: 0,
        tax: 0,
        utr: null,
        created_at: 1697439617,
      },
      {
        id: 'setl_MnAGDHdVNnkU49',
        entity: 'settlement',
        amount: 100,
        status: 'processed',
        fees: 0,
        tax: 0,
        utr: null,
        created_at: 1697085106,
      },
      {
        id: 'setl_Mn6eblRIEnEYXI',
        entity: 'settlement',
        amount: 114,
        status: 'processed',
        fees: 0,
        tax: 0,
        utr: null,
        created_at: 1697072405,
      },
    ],
  },
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
  settleBreakupDetailsWithNewBreakup,
  settlementMockRes,
};
