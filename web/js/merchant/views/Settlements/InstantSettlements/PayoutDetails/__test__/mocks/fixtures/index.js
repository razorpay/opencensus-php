jest.mock(
  'merchant/views/Settlements/InstantSettlements/PayoutDetails/DetailsListContainer',
  () =>
    ({ instantSettlement: { entity } }) =>
      (
        <div>
          <h4>Details List Container</h4>
          <span>{entity}</span>;
        </div>
      ),
);

jest.mock('merchant/views/Settlements/InstantSettlements/PayoutDetails/BreakupList', () => () => (
  <div>
    <h4>Breakup List</h4>
  </div>
));

export const state = {
  user:{
    mode: "test",
  },
  instantSettlement: {
    instantSettlement: {
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
            status: 'initiated',
            created_at: 1643017682,
          },
        ],
      },
    },
    loading: false,
    loadingTotalSettledAmount: false,
  },
};
