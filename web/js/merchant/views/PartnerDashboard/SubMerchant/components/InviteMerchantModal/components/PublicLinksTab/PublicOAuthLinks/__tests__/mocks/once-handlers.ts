import { rest } from 'msw';

import { referralData } from './fixtures';

export const fetchReferralsHandler = () =>
  rest.post(
    '*/partnerships/twirp/rzp.commissions.settings.v1.SettingsAPI/Upsert',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: { settings: referralData },
        }),
      );
    },
  );
