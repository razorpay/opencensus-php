const defaultWidgetState = {
  loading: false,
  data: null,
  updatedAt: null,
};

const lifetimeWidgetsDefaultState = {
  data: null,
  updatedAt: null,
  modalLoading: false,
  loading: false,
};

export const RTO_ANALYTICS_INIT_STATE = {
  magicRTOAnalytics: {
    startTime: null,
    endTime: null,
    loading: false,
    order_split: {
      ...defaultWidgetState,
    },
    order_split_cumulative: {
      ...defaultWidgetState,
    },
    feedback_rate: {
      ...defaultWidgetState,
    },
    flagged_reason: {
      ...defaultWidgetState,
    },
    rto_reasons: {
      ...defaultWidgetState,
    },
    cod_rate: {
      ...defaultWidgetState,
    },
    risky_orders: {
      ...defaultWidgetState,
    },
    rto_rate: {
      ...defaultWidgetState,
    },
    cost_saving: {
      ...defaultWidgetState,
    },
    intelligence_stat_performance: {
      ...defaultWidgetState,
    },
    rto_by_zipcode: {
      ...lifetimeWidgetsDefaultState,
    },
    rto_by_ip: {
      ...lifetimeWidgetsDefaultState,
    },
    timedWidgetsFetching: false,
  },
  magicCheckout: {
    cod_order_control: true,
  },
};

export const TABS = [
  {
    labelName: 'RTO Insights',
    displayText: 'RTO insights tab',
  },
  {
    labelName: 'Overview',
    displayText: 'Overview tab',
  },
  {
    labelName: 'Risk Report',
    displayText: 'Risk report tab',
  },
];

export const MANUAL_REVIEW_ORDER_SPLIT_DATA = JSON.parse(`[{
  "name": "Manual Review Order Split",
  "aggregation_type": "weekly",
  "updated_at": "1666297669",
  "manual_review_order_split": [
    {
      "total_order": 100,
      "approved": 57,
      "cancel": 3,
      "hold": 18,
      "no_action": 22,
      "period": "1683700022"
    },
    {
      "total_order": 100,
      "approved": 47,
      "cancel": 23,
      "hold": 18,
      "no_action": 12,
      "period": "1684564022"
    }
  ]
}]`);

export const MANUAL_RISK_ORDER_SPLIT_DATA = JSON.parse(`[{
  "name": "Risk Level Order Split",
  "aggregation_type": "weekly",
  "updated_at": "1666297669",
  "manual_risk_order_split": [
    {
      "total_order": 78,
      "high_risk": 57,
      "medium_risk": 3,
      "low_risk": 18,
      "period": "1683700022"
    },
    {
      "total_order": 88,
      "high_risk": 47,
      "medium_risk": 23,
      "low_risk": 18,
      "period": "1684564022"
    }
  ]
}]`);

export const PREPAY_INSIGHTS_DATA = [
  {
    label: 'COD to Prepaid Conversion',
    value: '10 %',
  },
  {
    label: '% of COD orders converted to Prepaid',
    value: '₹ 1',
  },
  {
    label: 'Total discount provided to customers',
    value: '₹ 17',
  },
];

export const PREPAY_INSIGHTS_EMPTY_DATA = [
  {
    label: 'COD to Prepaid Conversion',
    value: '--',
  },
  {
    label: '% of COD orders converted to Prepaid',
    value: '--',
  },
  {
    label: 'Total discount provided to customers',
    value: '--',
  },
];
