import { CommonApiResponse, PaginationParamsType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { ListFiltersType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { ActivationStatesT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

export interface AcceptedInvitesFiltersType extends ListFiltersType {
  activation_status?: string;
  application_id?: string;
  contact_info?: string;
  contact_mobile?: string;
  count: number;
  email: string;
  id?: string;
  name: string;
}

export interface PGAcceptedInviteItem {
  activated?: boolean;
  contact_mobile: string;
  created_at: number | string;
  dashboard_access?: boolean;
  email: string;
  id: string;
  name: string;
  updated_at: number | string;
  kyc_access: {
    state: string;
    rejection_count: number;
    token_expiry: number;
  };
  details: {
    activation_status: ActivationStatesT;
  };
  user: {
    email?: string;
    id: string;
    contact_mobile: string;
  };
}

export interface POSAcceptedInviteItem extends PGAcceptedInviteItem {
  pos: {
    success: boolean;
    activation_status: ActivationStatesT;
    last_kyc_performed_by: {
      contact_email: string;
      contact_no: string;
      name: string;
      type: string;
      id: string;
    };
  };
}

export type FetchInviteResponse = CommonApiResponse<{
  count: number;
  items: Array<PGAcceptedInviteItem> | Array<POSAcceptedInviteItem>;
}>;
export interface FetchSubmerchantsParams extends AcceptedInvitesFiltersType, PaginationParamsType {
  product: string;
}

export const fetchSubmerchants = (
  params: FetchSubmerchantsParams,
): Promise<FetchInviteResponse> => {
  return merchantFetch({
    url: 'submerchants',
    method: 'get',
    data: params,
  });
};
