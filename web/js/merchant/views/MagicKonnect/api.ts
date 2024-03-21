import { merchantFetch } from 'merchant/utils/ajax';

export const getJwtTokenForMagicKonnect = () => {
  return merchantFetch({
    url: `1cc/konnect/sso/jwt`,
    method: 'get',
  });
};
