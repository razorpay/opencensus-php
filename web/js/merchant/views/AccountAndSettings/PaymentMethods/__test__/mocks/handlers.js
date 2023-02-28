import merchantInstrumentStatusResponse from './merchant-instruments.json';
import { rest } from 'msw';

export const instrumentHandlers = [
  rest.get('*/merchant/api/:mode/merchant_instrument_status', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(merchantInstrumentStatusResponse), ctx.delay(50));
  }),
  rest.get('*/merchant/api/:mode/merchant_instruments', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: [],
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/:mode/terminals/proxy/discrepancy_list_merchant', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: [],
      }),
      ctx.delay(50),
    );
  }),
];
