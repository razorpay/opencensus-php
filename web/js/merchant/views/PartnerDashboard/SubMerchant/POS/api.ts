import { CommonApiResponse } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { PosSubmerchantDetailsResponseDataType } from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';

type FetchSubmerchantDetailsResponse = CommonApiResponse<PosSubmerchantDetailsResponseDataType>;

export const fetchPosSubmerchantDetails = (
  submerchantId: string,
): Promise<FetchSubmerchantDetailsResponse> => {
  return merchantFetch({
    url: `submerchants/${submerchantId}?product=pos`,
    method: 'get',
  });
};
