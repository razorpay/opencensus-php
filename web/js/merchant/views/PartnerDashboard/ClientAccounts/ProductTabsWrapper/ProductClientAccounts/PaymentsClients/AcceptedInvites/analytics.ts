import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

export const handleClientAccountSelected = (
  productType: string,
  submerchantId: string,
  applicationId = '',
): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Affiliate Account',
    actionName: 'Selected',
    screen: 'affiliate accounts',
    properties: {
      productType,
      submerchantId,
      applicationId,
    },
  });
};
