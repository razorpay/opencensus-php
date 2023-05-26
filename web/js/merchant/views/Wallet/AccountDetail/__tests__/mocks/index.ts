import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';
import { rest } from 'msw';
import { accountBalanceResponse, accountDetailResponse } from './fixtures';

export default [
  rest.get(`*/${WALLET_BASE_PATH}/accounts`, (req, res, ctx) => {
    const accountId = req.url.searchParams.get('issuing_account_id');
    if (accountId === 'iacc_I9eCvXfHx7nzZz') {
      return res(ctx.status(404), ctx.json({}), ctx.delay(100));
    }
    return res(ctx.status(200), ctx.json(accountDetailResponse), ctx.delay(100));
  }),
  rest.get(`*/${WALLET_BASE_PATH}/*/balance`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(accountBalanceResponse), ctx.delay(100));
  }),
];
