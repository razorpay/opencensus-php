import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

export const trackAcceptedInvitesClick = (productType): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Accepted Invites Partner Dashboard Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      productType,
    },
  });
};
export const trackAllInvitesClick = (productType): void => {
  analyticsTrackWithUserInfo({
    objectName: 'All Invites Partner Dashboard Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      productType,
    },
  });
};
