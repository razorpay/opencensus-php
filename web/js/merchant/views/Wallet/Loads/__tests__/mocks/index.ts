import { rest } from 'msw';
import { LoadsResponse } from 'merchant/views/Wallet/Loads/__tests__/mocks/fixtures';
import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

export default [
  rest.post(`*/${WALLET_BASE_PATH}/dashboard/loads`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(LoadsResponse), ctx.delay(100));
  }),
];
