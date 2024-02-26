import { rest } from 'msw';

import {
  allInvitesListSuccess,
  resendInviteHandler,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';

import { allInvitesDataPOS, resendInviteDataPOS, partnerAgentsData } from './fixtures';

export const allInvitesListSuccessPOS = (data = allInvitesDataPOS) => allInvitesListSuccess(data);
export const resendInviteHandlerPOS = (data = resendInviteDataPOS) => resendInviteHandler(data);
export const fetchPartnerAgentUsersHandler = () =>
  rest.get('*/merchant/api/*/merchants-users', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: partnerAgentsData,
      }),
    );
  });
