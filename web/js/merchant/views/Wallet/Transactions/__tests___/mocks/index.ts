import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';
import { listFundTransactionsResponse } from 'merchant/views/Wallet/Funds/Transactions/__tests__/mocks/fixtures';
import { rest } from 'msw';
import { listTransactionsResponse } from './fixtures';

export default [
  rest.post(`*/${WALLET_BASE_PATH}/dashboard/transactions`, (req, res, ctx) => {
    if (req.url.searchParams.get('type') == 'pool_transaction') {
      return res(ctx.status(200), ctx.json(listFundTransactionsResponse), ctx.delay(100));
    }
    return res(ctx.status(200), ctx.json(listTransactionsResponse), ctx.delay(100));
  }),
];
