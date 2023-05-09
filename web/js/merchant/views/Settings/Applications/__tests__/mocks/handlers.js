import { rest } from 'msw';

export const getApplications = (items) =>
  rest.get('*/merchant/api/*/oauth/submerchant/applications', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { items, count: items.length },
      }),
    );
  });

export const getTokens = (items) =>
  rest.get('*/merchant/api/*/oauth/tokens', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { items, count: items.length },
      }),
    );
  });
