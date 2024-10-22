import { merchantFetch } from 'merchant/utils/ajax';
import { getAppType } from 'merchant/views/MagicCheckout/utils/getAppType';

export const syncWithShopify = (dashboardView: string) => {
  return merchantFetch({
    url: 'magic/shipping/shopify/sync',
    method: 'post',
    data: {
      app_type: getAppType(dashboardView),
    },
  });
};

export const pollShippingProfiles = () => {
  return merchantFetch({
    url: 'magic/shipping/shopify/sync/status',
    method: 'get',
  });
};
