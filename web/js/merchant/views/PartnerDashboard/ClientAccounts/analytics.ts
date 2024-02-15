import { analyticsTrack, analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

export const trackAddMerchantClicked = (productType: string): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Add New Merchant',
    actionName: 'Clicked',
    screen: 'affiliate accounts',
    properties: {
      productType,
      location: 'navbar',
    },
    toCleverTap: true,
  });
};
export const trackProductTabClick = (productType: string, partner_id: string): void => {
  if (productType === PRODUCT_TYPE.CAPITAL) {
    analyticsTrack({
      screen: 'Affiliate accounts',
      objectName: 'partnerships.capital',
      actionName: 'affiliate accounts.tab clicked',
      properties: {
        partner_id,
      },
      toLumberjack: true,
    });
  } else {
    analyticsTrackWithUserInfo({
      screen: 'Affiliate Accounts',
      objectName: 'Product Tab',
      actionName: 'Clicked',
      properties: {
        partner_id,
      },
    });
  }
};
export const trackShareReferralLinkClicked = (productType: string, partner_id: string): void => {
  if (productType === PRODUCT_TYPE.CAPITAL) {
    analyticsTrack({
      screen: 'Affiliate accounts',
      objectName: 'partnerships.capital.affiliate accounts',
      actionName: 'share referral link clicked',
      properties: {
        partner_id,
      },
      toLumberjack: true,
    });
  } else {
    analyticsTrackWithUserInfo({
      objectName: 'Share Referral Link',
      actionName: 'clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'submerchant list',
      },
      toCleverTap: true,
    });
  }
};
