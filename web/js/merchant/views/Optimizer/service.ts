import { merchantFetch } from 'merchant/utils/ajax';

export const fetchSupportedGateways = () => {
  const params = {
    url: 'terminals/proxy/optimizer/supported_gateways',
    method: 'get',
  };
  return merchantFetch(params);
};
