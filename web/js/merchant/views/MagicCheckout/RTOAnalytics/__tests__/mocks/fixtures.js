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
  session: {
    user: {
      isMagicRTOAnalyticsV2Enabled: true,
    },
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
