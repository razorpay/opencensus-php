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
