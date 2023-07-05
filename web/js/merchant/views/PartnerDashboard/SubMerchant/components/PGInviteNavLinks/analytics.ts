import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

export const trackAcceptedInvitesClick = (): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Accepted Invites Partner Dashboard Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      productType: PRODUCT_TYPE.PG,
    },
  });
};
export const trackAllInvitesClick = (): void => {
  analyticsTrackWithUserInfo({
    objectName: 'All Invites Partner Dashboard Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      productType: PRODUCT_TYPE.PG,
    },
  });
};
