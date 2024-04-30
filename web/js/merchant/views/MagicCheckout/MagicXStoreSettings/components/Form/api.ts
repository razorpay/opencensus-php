import { merchantFetch } from 'merchant/utils/ajax';

export const postMagicXStoreSettings = (data) => {
  return merchantFetch({
    url: '1cc/magic/merchant/configs/shopify',
    method: 'post',
    data,
  });
};
