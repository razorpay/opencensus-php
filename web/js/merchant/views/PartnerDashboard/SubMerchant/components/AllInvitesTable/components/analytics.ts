import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

export const trackAccountLevelAllInvitesCta = ({
  name,
  email,
  contact_no,
  productType,
}: {
  name: string;
  email: string;
  contact_no: string;
  productType: string;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Account Level All Invites Tab Action Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      contactMobile: contact_no,
      contactEmail: email,
      accountName: name,
      action: 'Resend Invite',
      productType,
    },
  });
};
