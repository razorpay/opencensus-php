import { rest } from 'msw';

import {
  teamMembersData,
  teamMemberInvitationsData,
  sendMerchantInvitationSuccessData,
} from './fixtures';

export const fetchMerchantTeamMembersHandler = (data: any = teamMembersData) =>
  rest.get('*/merchant/api/*/merchants-users', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });

export const fetchMerchantInvitationsHandler = (data = teamMemberInvitationsData) =>
  rest.get('*/merchant/api/*/invitations', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });

export const sendMerchantInvitationError = (
  errors = ['Invitation is already sent to this email'],
) =>
  rest.post('*/merchant/api/*/invitations', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors,
      }),
    );
  });

export const sendMerchantInvitationSuccess = (data = sendMerchantInvitationSuccessData) =>
  rest.post('*/merchant/api/*/invitations', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });

export const fetchTeamInvitationsHandler = () => {};
