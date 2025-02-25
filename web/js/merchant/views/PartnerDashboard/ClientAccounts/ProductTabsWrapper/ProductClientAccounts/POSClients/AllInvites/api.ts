import { CommonApiResponse, User } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { SubmerchantInviteItem } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

// Re-exports from legacy code
export {
  type FetchInvitesParams,
  type FetchInviteResponse,
  fetchInvites,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

export interface POSSubmerchantInviteItem extends SubmerchantInviteItem {
  inviter_user_id: string;
  inviter_email: string;
}

export type PartnerAgentUser = {
  id: string;
  name?: string;
  email?: string;
  role: string;
};
export type POSAgents = Array<PartnerAgentUser>;
export type POSAgentsMap = Record<string, PartnerAgentUser & { inviterName?: string }>;
export const getPosAgentsMap = (user: User, posAgents?: POSAgents): POSAgentsMap => {
  const posAgentsMap = {} as POSAgentsMap;
  if (user.user) {
    const sessionUser = user.user;
    const role = user.role as string;
    posAgentsMap[sessionUser.id] = { inviterName: 'Self', role, ...sessionUser };
  }
  posAgents?.forEach((agent) => {
    posAgentsMap[agent.id] = { inviterName: agent.name, ...agent };
  });
  return posAgentsMap;
};

const ROLES_TO_DISPLAY = ['owner', 'partner_agent'];
export type FetchPartnerAgentUsersResponse = CommonApiResponse<POSAgents>;
export const fetchPartnerAgentUsers = async (): Promise<POSAgents> => {
  const merchantUsersData: FetchPartnerAgentUsersResponse = await merchantFetch({
    url: 'merchants-users',
    method: 'get',
    mode: 'live',
    data: {},
  });
  // parse the users data for owners/agents
  return (merchantUsersData.data || [])?.filter((agent) => ROLES_TO_DISPLAY.includes(agent.role));
};
