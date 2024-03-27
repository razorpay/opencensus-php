import { fetchMerchantTeamMembersHandler } from 'merchant/views/PartnerDashboard/PartnerManageTeam/__tests__/mocks/once-handlers';
import {
  allInvitesListSuccess,
  resendInviteHandler,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';

import { allInvitesDataPOS, resendInviteDataPOS, partnerAgentsData } from './fixtures';

export const allInvitesListSuccessPOS = (data = allInvitesDataPOS) => allInvitesListSuccess(data);
export const resendInviteHandlerPOS = (data = resendInviteDataPOS) => resendInviteHandler(data);
export const fetchPartnerAgentUsersHandler = (data = partnerAgentsData) =>
  fetchMerchantTeamMembersHandler(data);
