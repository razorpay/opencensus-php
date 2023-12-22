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

export const getFeatureHandler = () =>
  rest.post('*/feature/merchant/*/notify_via_whatsapp_plink', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          status: true,
        },
      }),
    );
  });
