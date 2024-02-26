import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { ActivationStatesT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

export interface SubmerchantPartial {
  id: string;
  name: string;
  email: string;
  created_at: number | string;
  details: { activation_status: ActivationStatesT };
  user?: { contact_mobile: string };
}

export const trackListFilterSectionCta = ({
  productType,
  inviteView,
  action,
}: {
  productType: string;
  action: string;
  inviteView: string;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Affiliates List Filter Section Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      inviteView,
      action,
      productType,
    },
  });
};

export const trackAccountLevelAcceptedInvitesCta = (
  submerchant: SubmerchantPartial,
  { productType, action }: { productType: string; action: string },
): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Account Level Accepted Invites Tab Action Cta',
    actionName: 'Clicked',
    screen: window.location.pathname,
    properties: {
      accountId: submerchant.id,
      accountName: submerchant.name,
      activationStatus: submerchant.details?.activation_status,
      contactEmail: submerchant.email,
      contactMobile: submerchant.user?.contact_mobile,
      inviteAcceptedON: submerchant.created_at,
      productType,
      action,
    },
  });
};
