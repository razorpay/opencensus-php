import { rest } from 'msw';

export const workflowSuccess = (data = {}) =>
  rest.get('*/merchant/bank_detail_update/details', (req, res, ctx) => {
    return res.once(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          ...data,
        },
      }),
      ctx.delay(50),
    );
  });
