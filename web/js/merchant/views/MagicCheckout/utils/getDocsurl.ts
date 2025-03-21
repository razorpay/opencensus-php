import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { PATHS } from 'merchant/views/MagicCheckout/constants';

const getDocsUrl = ({ activeTab = "", currentPlatform = "", org, currentPath = "" }: { activeTab?: string, currentPlatform?: string, org?: { business_name: string }, currentPath?: string }) => {
  const baseUrl = `https://${org?.business_name?.toLowerCase() || "razorpay"}.com/docs/payments/magic-checkout/`;
  const utmParams = '?utm_source=razorpay-dashboard&utm_medium=docs-link&utm_campaign=dash-magic-exp';

  const urlMap = {
    [PATHS.SHIPPING_SETUP]: {
      [PLATFORMS.VALUES.SHOPIFY]: `shopify/configuration/${utmParams}#shipping-options`,
      [PLATFORMS.VALUES.WOOCOMMERCE]: `woocommerce/configuration/${utmParams}#shipping-options`
    },
    [PATHS.COD_SETTINGS]: {
      [PLATFORMS.VALUES.SHOPIFY]: `shopify/configuration/${utmParams}#cash-on-delivery`,
      [PLATFORMS.VALUES.WOOCOMMERCE]: `woocommerce/configuration/${utmParams}#cash-on-delivery`
    },
    [PATHS.COUPONS]: `shopify/configuration/${utmParams}#coupons`,
    [PATHS.PARTIAL_COD]: `shopify/configuration/${utmParams}#partial-cash-on-delivery`,
    [PATHS.GOOGLE_ANALYTICS]: `shopify/configuration/${utmParams}#google-analytics`,
    [PATHS.FACEBOOK_ADS]: `shopify/configuration/${utmParams}#facebook-ads`
  };

  if (activeTab && urlMap[activeTab]) {
    return `${baseUrl}${urlMap[activeTab]}`;
  }

  if (urlMap[currentPath]) {
    if (typeof urlMap[currentPath] === 'string') {
      return `${baseUrl}${urlMap[currentPath]}`;
    } else if (urlMap[currentPath][currentPlatform]) {
      return `${baseUrl}${urlMap[currentPath][currentPlatform]}`;
    }
  }

  return `${baseUrl}${utmParams}`;
};
export default getDocsUrl;