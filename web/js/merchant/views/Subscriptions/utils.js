import { getKeysSeparatedByPipe, rupeesToPaise } from 'common/utils/rzp-utils';
import { isAmount } from 'common/utils/validators';
import analytics from './analytics';
import moment from 'moment';

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

function isRecurringChargeBulkEnabled() {
  return window.rzp_user?.experiments?.batch_service_recurring_charge_bulk?.result === 'on';
}

export function getRecurringChargeAPILabel() {
  return isRecurringChargeBulkEnabled() ? 'recurring_charge_bulk' : 'recurring_charge';
}

// For UPI and Card hides attempt charge
export function shouldEnableAttemptCharge(method, isDomesticMandate = false) {
  if (method === 'upi') {
    return false;
  }
  if (method === 'card') {
    return isDomesticMandate;
  }
  return true;
}

/**
 * @param {Date} startDate
 * @param {int} totalCount
 * @param {int} interval
 * @param {int} period
 * @returns Calculated end date of subscription
 */
export const getProbableEndDate = (startDate = moment(), totalCount, interval, period) => {
  const planPeriods = {
    daily: 'days',
    weekly: 'weeks',
    monthly: 'months',
    yearly: 'years',
  };
  return moment(startDate, 'X')
    .add(totalCount * interval, planPeriods[period])
    .add('days', 7)
    .format('DD MMM YYYY');
};
