import { rest } from 'msw';

import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';

import { allInvitesDataPOS, teamMembersData } from './fixtures';

export const allInvitesListSuccessPOS = (data = allInvitesDataPOS) => allInvitesListSuccess(data);
export const fetchPartnerAgentUsersHandler = () =>
  rest.get('*/merchant/api/*/merchants-users', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: teamMembersData,
      }),
    );
  });
