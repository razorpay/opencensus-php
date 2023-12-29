import { rest } from 'msw';
import { WalletReportLogResponse } from './fixtures';

export default [
  rest.get(`*/logs`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(WalletReportLogResponse), ctx.delay(0));
  }),
];
