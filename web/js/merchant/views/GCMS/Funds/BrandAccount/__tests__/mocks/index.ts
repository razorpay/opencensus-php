import { rest } from 'msw';

import { GCMS_BASE_PATH } from 'merchant/views/GCMS/shared/constants';

import { brandBalanceResponse } from './fixtures';

export default [
  rest.get(`${GCMS_BASE_PATH}/merchants/:merchantId/balances`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(brandBalanceResponse), ctx.delay(1));
  }),
];
