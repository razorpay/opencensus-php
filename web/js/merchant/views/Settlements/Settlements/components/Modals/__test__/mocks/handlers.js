import { rest } from 'msw';

export const breakupModalSuccessHandler = () =>
  rest.get('*/merchant/api/test/settlements/:id/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: {
          entity: 'collection',
          count: 3,
          items: [
            {
              component: 'adjustment',
              count: 5,
              type: 'credit',
              amount: 30900,
            },
            {
              component: 'unreconciled',
              count: 5,
              type: 'credit',
              amount: 134567,
            },
            {
              component: 'refund_domestic',
              count: 1,
              type: 'credit',
              amount: 0,
            },
          ],
        },
      }),
      ctx.delay(50),
    );
  });

export const breakupModalErrorHandler = () =>
  rest.get('*/merchant/api/test/settlements/:id/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: 'Something went wrong',
      }),
    );
  });
