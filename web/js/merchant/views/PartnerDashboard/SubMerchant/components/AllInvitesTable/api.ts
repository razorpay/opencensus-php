import { CommonApiResponse, PaginationParamsType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

import { AllInvitesFiltersType } from './components/AllInvitesFilter';

export type SubmerchantInviteItem = {
  id: string;
  name: string;
  email: string;
  contact_no: string;
  updated_at: string;
  created_at: string;
};

export type FetchInviteResponse = CommonApiResponse<{
  count: number;
  items: Array<SubmerchantInviteItem>;
}>;
export interface FetchInvitesParams
  extends Partial<AllInvitesFiltersType>,
    Partial<PaginationParamsType> {
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
