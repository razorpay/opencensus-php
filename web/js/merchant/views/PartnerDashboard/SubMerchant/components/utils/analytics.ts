import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

type SubmerchantPartial = {
  id: string;
  name: string;
  email: string;
  created_at: string;
  details: { activation_status: string };
  user?: { contact_mobile: string };
};

export const trackAcceptedInvitesCta = (
  submerchant: SubmerchantPartial,
  { properties = {}, ...args }: { properties: Record<string, string> },
): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Account Level Accepted Invites Tab Action Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    ...args,
    properties: {
      accountId: submerchant.id,
      accountName: submerchant.name,
      activationStatus: submerchant.details.activation_status,
      productType: PRODUCT_TYPE.PG,
      contactEmail: submerchant.email,
      contactMobile: submerchant.user?.contact_mobile,
      inviteAcceptedON: submerchant.created_at,
      ...properties,
    },
  });
};
