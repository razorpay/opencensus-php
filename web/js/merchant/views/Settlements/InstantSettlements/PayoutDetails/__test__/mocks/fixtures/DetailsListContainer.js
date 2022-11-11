jest.mock('merchant/views/Settlements/InstantSettlements/PayoutDetails/PayoutList', () => () => (
  <div>
    <h4>Payout List Items</h4>
  </div>
));

export const defaultProps = {
  instantSettlement: {
    id: 'setlod_InXEtIiuvilPhM',
    entity: 'settlement.ondemand',
    amount_requested: 10000,
    amount_settled: 9971,
    amount_pending: 0,
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
      count: 1,
      items: [
        {
          id: 'setlodp_InXEtJ23TyveQt',
          entity: 'settlement.ondemand_payout',
          initiated_at: 1643017683,
          processed_at: null,
          reversed_at: null,
          amount: 10000,
          amount_settled: 9971,
          fees: 29,
          tax: 4,
          utr: null,
          status: 'reversed',
          created_at: 1643017682,
        },
        {
          id: 'setlodp_InXEtJ23TyeQt',
          entity: 'settlement.ondemand_payout',
          initiated_at: 1643017683,
          processed_at: null,
          reversed_at: null,
          amount: 10000,
          amount_settled: 9971,
          fees: 29,
          tax: 4,
          utr: null,
          status: 'initiated',
          created_at: 1643017682,
        },
        {
          id: 'setlodp_InXEtJ23TyveQt',
          entity: 'settlement.ondemand_payout',
          initiated_at: 1643017683,
          processed_at: null,
          reversed_at: null,
          amount: 10000,
          amount_settled: 9971,
          fees: 29,
          tax: 4,
          utr: null,
          status: 'initiated',
          created_at: 1643017682,
        },
      ],
    },
  },
};

export const options = [
  {
    label: 'All',
    value: '',
  },
  {
    label: 'Created',
    value: 'created',
  },
  {
    label: 'Initiated',
    value: 'initiated',
  },
  {
    label: 'Processed',
    value: 'processed',
  },
  {
    label: 'Reversed',
    value: 'reversed',
  },
];
