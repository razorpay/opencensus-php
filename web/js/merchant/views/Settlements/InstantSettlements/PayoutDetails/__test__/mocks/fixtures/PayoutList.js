export const state = {
  session: {
    user: {},
    mode: 'live',
  },
};

export const defaultProps = {
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
      amount: 10000,
      amount_settled: 9971,
      fees: 29,
      utr: 'qlirejc',
      status: 'initiated',
      created_at: 1643017682,
    },
  ],
};

export const tableHeader = [
  'Ondemand Payout ID',
  'Requested Amount',
  'Settled Amount',
  'UTR',
  'Created At',
  'Status',
];
