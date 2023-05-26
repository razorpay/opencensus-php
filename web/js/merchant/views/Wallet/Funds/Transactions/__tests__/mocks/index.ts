import { rest } from 'msw';
import { listFundTransactionsResponse, fundsSummaryResponse } from './fixtures';

export default [
  rest.get('*/wallet/proxy/issuing/transactions', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(listFundTransactionsResponse), ctx.delay(1));
  }),
  rest.get('*/wallet/proxy/issuing/ipart_100000000000/balance', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(fundsSummaryResponse), ctx.delay(1));
  }),
];
