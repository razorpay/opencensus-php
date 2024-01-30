import { rest } from 'msw';

import { ordersListResponse } from './fixtures';

export default [
  rest.get(`*/gcoms/orders`, (req, res, ctx) => {
    if (req.url.searchParams.get('reseller_name') === 'abc') {
      return res(
        ctx.status(200),
        ctx.json({ ...ordersListResponse, data: { ...ordersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    if (req.url.searchParams.get('status') === 'cancelled') {
      return res(
        ctx.status(200),
        ctx.json({ ...ordersListResponse, data: { ...ordersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    return res(ctx.status(200), ctx.json(ordersListResponse), ctx.delay(100));
  }),
];
