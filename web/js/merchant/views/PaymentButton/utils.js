import LocalStorageService from 'common/utils/localStorage';

function getPaymentButtonLocalStorageKey({ mid, mode }) {
  if (!mid || !mode) return;

  return `rzp_onboarding_${mid}_${mode}_payment_button_used`;
}

export function getIsPaymentButtonCodeUsed(options) {
  const key = getPaymentButtonLocalStorageKey(options);

  if (!key) return;

  const value = LocalStorageService.getItem(key);
  return value === 'true' || value === true;
}

export function setIsPaymentButtonCodeUsed(options, isEnabled = true) {
  const key = getPaymentButtonLocalStorageKey(options);

  if (!key) return;

  LocalStorageService.setItem(key, isEnabled);
}
