import { rest } from 'msw';

export const magicXCODHandler = [
  rest.post('*/magic/shipping/shopify/sync', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 202,
        success: true,
        data: [],
      }),
    );
  }),
  rest.get('*/magic/shipping/shopify/sync/status', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        success: true,
        data: [],
      }),
    );
  }),
];
