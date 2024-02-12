import { rest } from 'msw';

import { getGCMSBasePath } from 'merchant/views/GCMS/shared/constants';

import { gcmsFundsResellerAccountsResponse } from './fixtures';

export default [
  rest.get(`*${getGCMSBasePath()}/merchants/:merchantId/resellers/balances`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(gcmsFundsResellerAccountsResponse), ctx.delay(1));
  }),
];
