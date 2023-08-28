import { rest } from 'msw';

import { referralData } from './fixtures';

export const fetchReferralsHandler = (state: { isApiCalled?: boolean } = {}) =>
  rest.post('*/merchant/api/:mode/merchant/referral', (req, res, ctx) => {
    state.isApiCalled = true;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { referrals: referralData },
      }),
    );
  });

export const createSubmerchantInviteSuccessHandler = () =>
  rest.post('*/partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Create', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
      }),
      ctx.delay(50),
    );
  });

export const createSubmerchantInviteErrorHandler = (message = 'Something went wrong') =>
  rest.post('*/partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Create', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: false,
        errors: [message],
      }),
      ctx.delay(50),
    );
  });
