import { CommonApiResponse, PaginationParamsType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { ListFiltersType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { ActivationStatesT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { SubmerchantPartial } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/analytics';

export interface AcceptedInvitesFiltersType extends ListFiltersType {
  activation_status?: string;
  application_id?: string;
  contact_info?: string;
  contact_mobile?: string;
  count: PaginationParamsType['count'];
  email: string;
  id?: string;
  name: string;
}

export interface PGAcceptedInviteItem extends SubmerchantPartial {
  activated?: boolean;
  contact_mobile: string;
  created_at: number | string;
  dashboard_access?: boolean;
  email: string;
  id: string;
  name: string;
  updated_at: number | string;
  details: {
    activation_status: ActivationStatesT;
  };
  user: {
    email?: string;
    id: string;
    contact_mobile: string;
  };
  kyc_access?: null | {
    state: string;
    rejection_count: number;
    token_expiry: number;
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
  count: PaginationParamsType['count'];
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
