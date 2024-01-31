import { rest } from 'msw';

import { brandBalanceResponse } from './fixtures';

export default [
  rest.get('gcoms/merchants/:merchantId/balances', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(brandBalanceResponse), ctx.delay(1));
  }),
];
