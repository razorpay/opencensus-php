// This file be used - once the api integration is done and i will modify the testcases
import { rest } from 'msw';
import {
  CHECKOUT_COLLECTIONS_LIST,
  RCOD_COLLECTIONS_LIST,
} from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/contants';

export const magicCouponEngineHandler = [
  rest.get('*/1cc/dashboard/ce/coupons/sync', (req, res, ctx) =>
    res(
      ctx.status(200),
      ctx.json({
        data: {
          status: 'not-started',
        },
      }),
    ),
  ),
  rest.get(
    '*/1cc/dashboard/ce/coupons?type=&code=&status=&sort_by=&skip=0&count=10',
    (req, res, ctx) =>
      res(
        ctx.status(200),
        ctx.json({
          data: {
            coupons: [],
          },
        }),
      ),
  ),
  rest.get('*/1cc/magic/platform/products/collections/search', (req, res, ctx) => {
    const isRcodEnabled = req.url.searchParams.get('app_type') === 'sopc';
    const COLLECTIONS = isRcodEnabled ? RCOD_COLLECTIONS_LIST : CHECKOUT_COLLECTIONS_LIST;

    return res(
      ctx.status(200),
      ctx.json({
        data: {
          collections: COLLECTIONS,
        },
      }),
    );
  }),
];

export const magicCEConfigHandlers = {
  success: rest.post('*/merchant/api/:mode/1cc/merchant/configs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: [],
      }),
    );
  }),
  badRequest: rest.post('*/merchant/api/:mode/1cc/merchant/configs', (req, res, ctx) => {
    return res(ctx.status(400));
  }),
};
