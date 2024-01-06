// This file be used - once the api integration is done and i will modify the testcases
import { rest } from 'msw';

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
    return res(
      ctx.status(200),
      ctx.json({
        data: {
          collections: [
            {
              id: '1',
              title: 'test1',
            },
            {
              id: '2',
              title: 'test2',
            },
          ],
        },
      }),
    );
  }),
];
