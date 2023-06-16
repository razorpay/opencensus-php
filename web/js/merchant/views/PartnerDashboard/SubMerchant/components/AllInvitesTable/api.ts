import { merchantFetch } from 'merchant/utils/ajax';
import { AllInvitesFiltersType } from './components/AllInvitesFilter';
import { CommonApiResponse, PaginationParamsType } from 'common/typings';

export type SubmerchantInviteItem = {
  id: string;
  email: string;
  contact_no: string;
  updated_at: string;
  created_at: string;
};

type FetchInviteResponse = CommonApiResponse<{
  count: number;
  items: Array<SubmerchantInviteItem>;
}>;
export interface FetchInvitesParams extends AllInvitesFiltersType, PaginationParamsType {
  product: string;
}

type ResendInviteResponse = { success: boolean };

export const fetchInvites = (
  partnerId: string,
  params: FetchInvitesParams,
): Promise<FetchInviteResponse> => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/List',
    method: 'post',
    mode: 'live',
    data: {
      ...params,
      partner_id: partnerId,
    },
  });
};

export const resendInvite = (inviteId: string): Promise<ResendInviteResponse> => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Resend',
    method: 'post',
    mode: 'live',
    data: {
      invite_id: inviteId,
    },
  });
};
