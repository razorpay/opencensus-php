export const defaultProps = {
  settlement: {
    id: 'setlod_InXEtIiuvilPhM',
    entity: 'settlement.ondemand',
    amount_requested: 10000,
    amount_settled: 0,
    amount_pending: 9971,
    amount_reversed: 0,
    fees: 29,
    tax: 4,
    currency: 'INR',
    settle_full_balance: false,
    status: 'initiated',
    description: null,
    notes: [],
    created_at: 1643017682,
    scheduled: false,
    ondemand_payouts: {
      entity: 'collection',
      count: 2,
      items: [
        {
          id: 'setlodp_InXEtJ23TyveQt',
          amount: 10000,
          amount_settled: 9971,
          fees: 29,
          utr: null,
          status: 'initiated',
          created_at: 1643017682,
        },
        {
          id: 'setlodp_InXEtJ23Wqsse3',
          amount: 11000,
          amount_settled: 10971,
          fees: 29,
          utr: 'qlirejc',
          status: 'created',
          created_at: 1643017682,
        },
      ],
    },
  },
  isLoading: false,
};

export const tableHeader = ['UTR', 'Payout Amount', 'Status'];
