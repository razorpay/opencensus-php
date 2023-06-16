import { rest } from 'msw';
import { allInvitesData, resendInviteData } from './fixtures';

export const allInvitesListSuccess = (response = allInvitesData) => {
  return rest.post(
    '*/partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/List',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(response), ctx.delay(50));
    },
  );
};

export const allInvitesListError = () => {
  return rest.post(
    '*/partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/List',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['There was an error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    },
  );
};

export const resendInviteHandler = (response = resendInviteData) => {
  return rest.post(
    '*/partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Resend',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(response), ctx.delay(50));
    },
  );
};
