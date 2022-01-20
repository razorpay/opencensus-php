import { getUser, getMode } from 'merchant/store';
import { getItem, setItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

// 'BANNER_EXPIRY' represents number in day/s
const BANNER_EXPIRY = {
  rzp_banner_IntlPaymentsRecommendation: 30,
  rzp_banner_EnableInternationalPayments: 30,
  rzp_banner_LinkPayPalAccount: 30,
};

export const generateKey = (key) => {
  const mode = getMode();
  const user = getUser();
  return `${key}_${mode}_${user.current}`;
};

export const setInitalState = (key) => {
  const _KEY = generateKey(key);
  const data = {
    isEnabled: true,
    initialVisitOn: Date.now(),
    expiredOn: null,
  };
  setItem(_KEY, JSON.stringify(data));
  return data;
};

export const getLocalState = (key) => {
  const _KEY = generateKey(key);
  const state = getItem(_KEY);
  if (!state) return setInitalState(key);
  return JSON.parse(state);
};

export const setLocalState = ({ key, data }) => {
  const _KEY = generateKey(key);
  const localState = getLocalState(key);
  const state = JSON.stringify({
    ...localState,
    ...data,
  });
  setItem(_KEY, state);
};

export const setExpiry = (key) => {
  const localState = getLocalState(key);
  if (!localState.isEnabled) return localState;
  const data = {
    isEnabled: false,
    expiredOn: Date.now(),
  };
  setLocalState({ key, data });
  return data;
};

export const getDurationBetweenDates = (start, end) => {
  const _now = end || Date.now();
  const diff = _now - start;
  const duration = Math.round(diff / (1000 * 3600 * 24)); // Difference in Days
  return duration;
};

export const bannerExpired = (key) => {
  const { isEnabled, initialVisitOn, expiredOn } = getLocalState(key);
  if (!isEnabled) return true; // if 'isEnabled' is 'false' then banner is already expired

  const currentDate = expiredOn || Date.now();
  const duration = getDurationBetweenDates(initialVisitOn, currentDate);
  const expiryTime = BANNER_EXPIRY[key] || 30;

  if (duration > expiryTime) return true;

  return false;
};

export const analyticsFn = ({ eventName, event }) => {
  analyticsTrack({
    objectName: eventName,
    actionName: event,
    screen: 'Home Page',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};

/**
 * 
 * @param { international_cards_enabled: boolean, paypal: boolean, international_activation_form_initiated: boolean, international_activation_form_completed: boolean} data

 * @returns {
    enableRecommendationCard: boolean,
    enableIntlCards: boolean,
    enableLinkPaypal: boolean,
  }
 *
 */

export const computeBannerState = (data = {}) => {
  const { international_cards_enabled, paypal } = data;

  const user = getUser();
  const isBusinessNotRegistered = [2, 11, 12].includes(user?.business_type); // check if MID is not registered
  let enableRecommendationCard = false;
  let enableIntlCards = false;
  let enableLinkPaypal = false;
  let enableRecommendationCardExpired = bannerExpired('rzp_banner_IntlPaymentsRecommendation');
  let enableIntlCardsExpired = false;
  let enableLinkPaypalExpired = false;
  const isEitherOneTrue = Object.values(data).some(Boolean);

  // IntlPaymentsRecommendation - Start
  if (isEitherOneTrue || enableRecommendationCardExpired) {
    setExpiry('rzp_banner_IntlPaymentsRecommendation');
    enableRecommendationCardExpired = true;
    enableRecommendationCard = false;
  }

  const intlRecommendationState = getLocalState('rzp_banner_IntlPaymentsRecommendation');

  // if Recommendation Card is expired then don't trigger analytics event
  if (intlRecommendationState.isEnabled) {
    enableRecommendationCard = true;
    analyticsFn({ eventName: 'International payments recommendation', event: 'displayed' });
  }
  // IntlPaymentsRecommendation - End

  // EnableInternationalPayments - Start
  if (enableRecommendationCardExpired) {
    enableIntlCardsExpired = bannerExpired('rzp_banner_EnableInternationalPayments');

    if (enableIntlCardsExpired || international_cards_enabled || isBusinessNotRegistered) {
      // Show only for Registered Business
      setExpiry('rzp_banner_EnableInternationalPayments');
      enableIntlCardsExpired = true;
      enableIntlCards = false;
    }

    const intlCardState = getLocalState('rzp_banner_EnableInternationalPayments');
    if (intlCardState.isEnabled) {
      enableIntlCards = true;
      analyticsFn({ eventName: 'Enable international cards snackbar', event: 'displayed' });
    }
  }
  // EnableInternationalPayments - End

  // LinkPayPalAccount - Start
  if (enableRecommendationCardExpired && enableIntlCardsExpired) {
    enableLinkPaypalExpired = bannerExpired('rzp_banner_LinkPayPalAccount');

    if (paypal || enableLinkPaypalExpired) {
      setExpiry('rzp_banner_LinkPayPalAccount');
      enableLinkPaypalExpired = true;
      enableLinkPaypal = false;
    }
    const linkPaypalState = getLocalState('rzp_banner_LinkPayPalAccount');
    if (linkPaypalState.isEnabled) {
      enableLinkPaypal = true;
      analyticsFn({ eventName: 'Link paypal snackbar', event: 'displayed' });
    }
  }
  // LinkPayPalAccount - End

  return {
    enableRecommendationCard,
    enableIntlCards,
    enableLinkPaypal,
  };
};
