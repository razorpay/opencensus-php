import { rest } from 'msw';

export const fetchSettlementDetails = ({ type }) => {
  return rest.get('/merchant/api/:mode/settlements/:settlementId/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: 1,
          entity: 'collection',
          has_aggregated_fee_tax: false,
          items:
            type === 'empty'
              ? []
              : [
                  {
                    amount: 24600,
                    component: 'payment_domestic',
                    count: 2,
                    fee: 714,
                    tax: 0,
                    type,
                  },
                ],
        },
      }),
      ctx.delay(50),
    );
  });
};

export const mockFetchSettlementConfig = (status = true) => {
  return rest.post('*/settlements/dashboard/merchant_config/get', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          config: {
            features: {
              hold: {
                status,
              },
            },
            schedules: {
              payment: {
                'domestic:default': 'T+2 Working days',
                'international:default': 'T+7 Working days',
              },
            },
          },
        },
      }),
    );
  });
};
