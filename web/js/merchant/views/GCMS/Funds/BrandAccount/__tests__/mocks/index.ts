import { rest } from 'msw';

import { getGCMSBasePath } from 'merchant/views/GCMS/shared/constants';

import { brandBalanceResponse } from './fixtures';

export default [
  rest.get(`*${getGCMSBasePath()}/merchants/:merchantId/balances`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(brandBalanceResponse), ctx.delay(1));
  }),
];
