import LocalStorageService from 'rzp/utils/localStorage';

export const getQuickGuideLocalStorageKey = (props, feature) => {
  if (!props) {
    return;
  }

  const merchant = props.user.merchants[props.user.current];

  return `rzp_onboarding_${merchant.id}_${
    props.mode
  }_${feature}_quick_guide_closed`;
};

export const getQuickGuideIsClosed = (props, feature) => {
  if (!props) {
    return;
  }

  const localStorageKey = getQuickGuideLocalStorageKey(props, feature);

  let isQuickGuideClosed = LocalStorageService.getItem(localStorageKey);
  isQuickGuideClosed =
    isQuickGuideClosed === 'false' || !isQuickGuideClosed ? false : true;

  return isQuickGuideClosed;
};
