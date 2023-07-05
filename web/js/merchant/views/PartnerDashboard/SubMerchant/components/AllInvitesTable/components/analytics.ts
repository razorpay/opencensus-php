import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

export const trackAllInvitesCta = ({
  name,
  email,
  contact_no,
}: {
  name: string;
  email: string;
  contact_no: string;
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
      productType: PRODUCT_TYPE.PG,
    },
  });
};
