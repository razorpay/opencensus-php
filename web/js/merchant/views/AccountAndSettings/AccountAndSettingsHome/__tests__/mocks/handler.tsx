import { rest } from 'msw';
import { merchantInstrumentResponse, requestedInstrumentsResponse } from './response';

export const fetchMerchantInstrumentHandler = () =>
  rest.get('*/merchant_instrument_status', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: merchantInstrumentResponse,
      }),
      ctx.delay(50),
    );
  });

export const fetchRequestedInstrumentHandler = () =>
  rest.get('*/merchant_instruments', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: requestedInstrumentsResponse,
      }),
      ctx.delay(50),
    );
  });
