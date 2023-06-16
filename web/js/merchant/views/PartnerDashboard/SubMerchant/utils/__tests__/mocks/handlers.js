import { rest } from 'msw';

export const isEasyEnabledHandler = (enabled) =>
  rest.post(
    '*/partnerships/twirp/rzp.partnerships.merchant.v1.MerchantAPI/IsEasyEnabled',
    (req, res, ctx) =>
      res.once(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: { enabled },
        }),
      ),
  );
