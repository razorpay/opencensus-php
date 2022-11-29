import { rest } from 'msw';
import { getKeys, PLATFORM_LINKS, SUPPORTED_PLUGINS } from './fixtures';

export const pluginHandlers = [
  rest.get('*/merchant/api/:mode/onboarding/merchant/supported_plugins', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: Object.values(SUPPORTED_PLUGINS),
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/:mode/onboarding/merchants/:mechantId/plugin', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        success: true,
        data: [
          {
            website: PLATFORM_LINKS.SUCCESS.business_website,
            merchant_selected_plugin: 'Shopify',
            suggested_plugin: 'Wix',
          },
        ],
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/merchant/api/:mode/onboarding/merchants/:mechantId/plugin', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        success: true,
      }),
      ctx.delay(50),
    );
  }),
];

export const keyHandlers = [
  // download key
  rest.post('*/keys/csv', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json({ data: { success: true } }), ctx.delay(50));
  }),
  // generate key
  rest.post('*/merchant/api/:mode/keys/', (req, res, ctx) => {
    const { mode } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: getKeys(mode)[1],
      }),
      ctx.delay(50),
    );
  }),
];
