import { rest } from 'msw';

export const getMerchantFeaturesHandler = () =>
  rest.post('*/merchants/me/features', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {},
      }),
    );
  });
