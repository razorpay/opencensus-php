import { rest } from 'msw';
import { config } from './fixtures';

export const partnerConfigFetchHandlers = [
  rest.get('*/merchant/api/test/partner_config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: config,
      }),
      ctx.delay(50),
    );
  }),
];

export const partnerConfigSaveHandlers = [
  rest.post('*/merchant/api/test/partner_config/*/logo', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: config,
      }),
      ctx.delay(50),
    );
  }),

  rest.put('*/merchant/api/test/partner_config/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: config,
      }),
      ctx.delay(50),
    );
  }),
];
