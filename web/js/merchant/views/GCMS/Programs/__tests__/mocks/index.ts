import { rest } from 'msw';

import { accountDetailResponse } from 'merchant/views/Wallet/AccountDetail/__tests__/mocks/fixtures';
import { WALLET_BASE_PATH } from 'merchant/views/Wallet/constants';

import { programsResponse } from './fixtures';
import { CampaignWalletsResponse } from 'merchant/views/Wallet/Campaigns/__tests__/mocks/fixtures';

export default [
  rest.get(`*/${WALLET_BASE_PATH}/programs`, (req, res, ctx) => {
    const programId = req.url.searchParams.get('program_id');
    const programType = req.url.searchParams.get('program_type');

    if (programId === 'iprog_NEC3fO5GTvvX2S') {
      return res(ctx.status(404), ctx.json({}), ctx.delay(100));
    }
    if (programId === 'iprog_NEC3fO5GTvvX2Z') {
      return res(ctx.status(200), ctx.json(accountDetailResponse), ctx.delay(100));
    }
    //This is for fetching programs during campaign creation. Used in this test: merchant/views/Wallet/Campaigns/__tests__/CreateNewCampaign.test.tsx
    if (programType === 'wallet') {
      return res(ctx.status(200), ctx.json(CampaignWalletsResponse), ctx.delay(100));
    }

    return res(ctx.status(200), ctx.json(programsResponse), ctx.delay(100));
  }),
];
