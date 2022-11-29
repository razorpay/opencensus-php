export const defaultProps = {
  settlements: [
    {
      id: 'setlodp_InXEtJ23TyveQt',
      amount: 10000,
      amount_settled: 9971,
      amount_requested: 10000,
      fees: 29,
      scheduled: false,
      utr: null,
      status: 'initiated',
      created_at: 1643017682,
    },
    {
      id: 'setlodp_InXEtJ23Wqsse3',
      amount: 10000,
      amount_settled: 9971,
      fees: 29,
      scheduled: true,
      utr: 'qlirejc',
      amount_requested: 10000,
      status: 'initiated',
      created_at: 1643017682,
    },
  ],
  isLoading: false,
};

export const state = {
  session: {
    user: {
      isFeatureEnabled: () => false,
    },
    mode: 'live',
  },
  settlement: {
    holidayList: {
      loading: false,
      data: {},
      error: null,
    },
  },
  instantSettlements: {
    loading: false,
    items: defaultProps.settlements,
    error: null,
  },
  home: {
    ondemand_restrictions: {
      loading: false,
      data: {
        settlable_amount: 2000,
      },
      error: null,
    },
    current_balance: { loading: true, data: {}, error: null },
  },
};
