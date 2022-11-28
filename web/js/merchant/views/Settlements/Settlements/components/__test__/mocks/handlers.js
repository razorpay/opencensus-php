import { rest } from 'msw';

const ONDEMAND_SETTLEMENTS_HANDLERS = [
  rest.get('*merchant/api/test/settlement/ondemand/fees/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: 2,
          items: [
            {
              name: 'settlement_ondemand',
              amount: 1485,
              percentage: null,
              pricing_rule_id: 'IogfAHWKH1u8im',
              pricing_rule: {
                percent_rate: 15,
                fixed_rate: 0,
              },
            },
            {
              name: 'tax',
              amount: 268,
              percentage: 1800,
              pricing_rule_id: null,
            },
          ],
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.post('*/merchant/api/test/settlement/ondemand/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: 2,
          items: [
            {
              name: 'settlement_ondemand',
              amount: 1485,
              percentage: null,
              pricing_rule_id: 'IogfAHWKH1u8im',
              pricing_rule: {
                percent_rate: 15,
                fixed_rate: 0,
              },
            },
            {
              name: 'tax',
              amount: 268,
              percentage: 1800,
              pricing_rule_id: null,
            },
          ],
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/test/settlements/ondemand/feature/validate', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: 'The requested URL was not found on the server.',
      }),
    );
  }),
];

export default ONDEMAND_SETTLEMENTS_HANDLERS;
