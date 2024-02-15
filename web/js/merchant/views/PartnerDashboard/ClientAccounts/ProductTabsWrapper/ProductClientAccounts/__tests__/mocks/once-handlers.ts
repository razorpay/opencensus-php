import { rest } from 'msw';

import { accountsListResponse, submerchantWithKYCAccess } from './fixtures';

export const acceptedInvitesListHandler = (data = accountsListResponse) =>
  rest.get('*/merchant/api/*/submerchants', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
      ctx.delay(50),
    );
  });

export const submerchantDetailsHandler = (data = submerchantWithKYCAccess) =>
  rest.get('*/merchant/api/*/submerchants/:submerchantId', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
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
