import { rest } from 'msw';

import { accountDetailResponse } from 'merchant/views/Wallet/AccountDetail/__tests__/mocks/fixtures';
import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

import { programsResponse } from './fixtures';

export default [
  rest.get(`*/${WALLET_BASE_PATH}/programs`, (req, res, ctx) => {
    const programId = req.url.searchParams.get('program_id');
    if (programId === 'iprog_NEC3fO5GTvvX2S') {
      return res(ctx.status(404), ctx.json({}), ctx.delay(100));
    }
    if (programId === 'iprog_NEC3fO5GTvvX2Z') {
      return res(ctx.status(200), ctx.json(accountDetailResponse), ctx.delay(100));
    }
    return res(ctx.status(200), ctx.json(programsResponse), ctx.delay(100));
  }),
];
