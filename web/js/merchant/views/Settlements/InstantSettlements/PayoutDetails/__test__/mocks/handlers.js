import { rest } from 'msw';

export const payoutDetailsHandlers = [
  rest.get('*/settlements/ondemand/setlod_InXEtIiuvilPhM', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
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
      }),
      ctx.delay(50),
    );
  }),
];
