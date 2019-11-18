import LocalStorageService from 'common/utils/localStorage';

import { getUser, getMode } from 'merchant/store';

export const getQuickGuideLocalStorageKey = feature => {
  const mode = getMode(),
    user = getUser();

  return `rzp_onboarding_${user.current}_${mode}_${feature}_quick_guide_closed`;
};

export const getQuickGuideIsClosedFromLocalStorage = feature => {
  if (!feature) {
    return false;
  }

  const localStorageKey = getQuickGuideLocalStorageKey(feature);

  let localState =
    JSON.parse(LocalStorageService.getItem(localStorageKey)) || {};

  return !!localState.isClosed;
};

export const setQuickGuideIsClosedInLocalStorage = (
  feature,
  isClosed = true
) => {
  const localStorageKey = getQuickGuideLocalStorageKey(feature);

  LocalStorageService.setItem(
    localStorageKey,
    JSON.stringify({
      isClosed,
    })
  );
};
