import { CommonApiResponse } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

type FetchSubmerchantDetailsResponse = CommonApiResponse<PGAcceptedInviteItem>;

export const fetchSubmerchantDetails = (
  submerchantId: string,
  productType: string = PRODUCT_TYPE.PG,
): Promise<FetchSubmerchantDetailsResponse> => {
  return merchantFetch({
    url: `submerchants/${submerchantId}?product=${productType}`,
    method: 'get',
  });
};
