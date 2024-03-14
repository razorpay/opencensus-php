import { merchantFetch } from 'merchant/utils/ajax';
import { CommonApiResponse } from 'common/typings';

type fetchPartnerFeeFeatureResponse = CommonApiResponse<{
  feature_enabled: boolean;
}>;
export const fetchPartnerFeeFeature = (): Promise<fetchPartnerFeeFeatureResponse> => {
  return merchantFetch({
    url: 'submerchant/partner_feature_check/partner_plat_fee_invoice',
    method: 'get',
  });
};
