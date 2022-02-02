import { getKeysSeparatedByPipe, rupeesToPaise } from 'common/utils/rzp-utils';
import { isAmount } from 'common/utils/validators';
import analytics from './analytics';

export function trackSearchEvent(event, { eventStartLabel, options }) {
  if (!event) return;

  if (typeof event === 'string') {
    analytics.track(`${eventStartLabel}.${event}`, options);
  } else {
    const label = getKeysSeparatedByPipe(event);
    if (label && label.length > 0) {
      analytics.track(`${eventStartLabel}.${label}`, options);
    }
  }
}

export function isAmountLiesInRange(amount, maxAmountInPaisa = Infinity, minAmountInPaisa = 1) {
  const isValidAmount = isAmount(amount);
  if (!isValidAmount) return false;

  const amountInPaisa = rupeesToPaise(Number(amount));
  return isValidAmount && amountInPaisa >= minAmountInPaisa && amountInPaisa <= maxAmountInPaisa;
}
