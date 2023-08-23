import { rest } from 'msw';
import { PaymentsResponse } from 'merchant/views/Wallet/Payments/__tests__/mocks/fixtures';
import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

export default [
  rest.post(`*/${WALLET_BASE_PATH}/dashboard/payments`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(PaymentsResponse), ctx.delay(100));
  }),
];
