import { rest } from 'msw';

export const fetchWorkflowDetails = (data = {}) =>
  rest.get('*/merchant/toggle_international_revamped/details', (req, res, ctx) => {
    return res.once(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          ...data,
        },
      }),
    );
  });
