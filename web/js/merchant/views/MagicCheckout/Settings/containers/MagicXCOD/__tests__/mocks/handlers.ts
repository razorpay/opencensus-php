import { rest } from 'msw';

import {
  shippingRules,
  paymentRules,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/table';

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

export const magicxACODHandler = [
  rest.get('*/magic/sopc/customisations/rules', (_, res, ctx) =>
    res(
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          rules: [...shippingRules, ...paymentRules],
        },
      }),
    ),
  ),
];
