import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';
import { rest } from 'msw';
import { listTransactionsResponse } from './fixtures';

export default [
  rest.get(`*/${WALLET_BASE_PATH}/transactions`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(listTransactionsResponse), ctx.delay(100));
  }),
];
