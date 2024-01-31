import { rest } from 'msw';

import { gcmsFundsResellerAccountsResponse } from './fixtures';

export default [
  rest.get('*/gcoms/merchants/:merchantId/resellers/balances', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(gcmsFundsResellerAccountsResponse), ctx.delay(1));
  }),
];
