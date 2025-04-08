import { ANALYTICS } from 'common/constant';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

import { REKYC_STATUS_OPTIONS } from './constants';

export const isEligibleForReKyc = (splitz, user: User) => {
  return (
    isExperimentEnabled(splitz?.abExperiments?.enable_manual_rekyc) &&
    user.isCountryIndia &&
    user.isOrgRZP &&
    user.isActivated
  );
};

export const track = (user, widget, action, properties = {}) => {
  analyticsTrack({
    objectName: `manual re-kyc ${widget}`,
    actionName: action,
    screen: ANALYTICS.SCREEN.DASHBOARD,
    properties: {
      ...getCommonAnalyticsProperties(user),
      ...properties,
      rekyc_status: user.rekyc_status,
    },
  });
};

export const onClickRedirectNC = (user, widget) => {
  track(user, widget, 'clicked');
  window.open(
    `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification?isRekyc=true`,
    '_blank',
    'noreferrer noopener',
  );
};

export const onClickRedirectVKYC = async (user, widget, notify) => {
  try {
    const { success, data } = await merchantFetch({ url: 'vkyc', method: 'POST', mode: 'live' });
    if (!success || !data.details.weblink) {
      throw new Error('Could not get Video KYC Link');
    }

    track(user, widget, 'clicked', { get_vkyc_link: success });
    window.open(data.details.weblink, '_blank', 'noreferrer noopener');
  } catch {
    track(user, widget, 'error', { get_vkyc_link: false });
    notify({ message: 'Could not get Video KYC Link. Contact support.', type: 'error' });
  }
};

export const getParamsFromUser = (user: User) => {
  const status = user.rekyc_status;
  const canPerformActions = user.isAdminOrOwner;
  const ncCount = (user.manual_rekyc?.status ?? []).filter(
    (changelog) => changelog?.rekyc_status === REKYC_STATUS_OPTIONS.NEEDS_CLARIFICATION,
  ).length;

  return {
    status,
    canPerformActions,
    ncCount,
  };
};
