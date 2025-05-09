const PAYMENT_HANDLE_PREFIX = '@';

//removes @ from the beginning of the paymentHandleSlug
export const removePaymentHandleSlugPrefix = (paymentHandleSlug: string): string => {
  if (!paymentHandleSlug) {
    return '';
  }
  if (paymentHandleSlug.startsWith(PAYMENT_HANDLE_PREFIX)) {
    return paymentHandleSlug.slice(1);
  }
  return paymentHandleSlug;
};

export const toLowestAmountDenomination = (value: number): number => {
  return value * 100;
};

//adds @ in the beginning of the paymentHandleSlug
export const addPaymentHandleSlugPrefix = (paymentHandleSlug: string): string => {
  if (!paymentHandleSlug) {
    return `${PAYMENT_HANDLE_PREFIX}`;
  }
  if (paymentHandleSlug.startsWith(PAYMENT_HANDLE_PREFIX)) {
    return paymentHandleSlug;
  }
  return `${PAYMENT_HANDLE_PREFIX}${paymentHandleSlug}`;
};
