import { CommonApiResponse, User } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { SubmerchantInviteItem } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

// Re-exports from legacy code
export {
  FetchInvitesParams,
  FetchInviteResponse,
  fetchInvites,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

export interface POSSubmerchantInviteItem extends SubmerchantInviteItem {
  inviter_user_id: string;
  inviter_email: string;
}

export type PartnerAgentUser = {
  id: string;
  name: string;
};
export type POSAgents = Array<PartnerAgentUser>;
export type POSAgentsMap = Record<string, PartnerAgentUser & { inviterName: string }>;
export const getPosAgentsMap = (
  user: User,
  partnerAgentsData?: FetchPartnerAgentUsersResponse,
): POSAgentsMap => {
  const posAgentsMap = {} as POSAgentsMap;
  if (user.user) {
    const sessionUser = user.user;
    posAgentsMap[sessionUser.id] = { inviterName: 'Self', ...sessionUser };
  }
  partnerAgentsData?.data?.users.forEach((agent) => {
    posAgentsMap[agent.id] = { inviterName: agent.name, ...agent };
  });
  return posAgentsMap;
};
export type FetchPartnerAgentUsersResponse = CommonApiResponse<{
  role_id: string;
  role_name: string | null;
  merchant_id: string;
  users: Array<PartnerAgentUser>;
}>;
export const fetchPartnerAgentUsers = (): Promise<FetchPartnerAgentUsersResponse> => {
  return merchantFetch({
    url: 'merchants-users',
    method: 'get',
    mode: 'live',
    data: {
      role: 'partner_agent',
    },
  });
};
