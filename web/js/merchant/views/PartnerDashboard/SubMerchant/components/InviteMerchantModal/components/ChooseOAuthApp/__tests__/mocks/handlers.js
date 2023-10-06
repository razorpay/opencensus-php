import { rest } from 'msw';

export const getApplications = (items) =>
  rest.get('*/merchant/api/*/oauth/applications', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { items, count: items.length },
      }),
    );
  });
