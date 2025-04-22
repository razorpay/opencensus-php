import { getKeysSeparatedByPipe, rupeesToPaise } from 'common/utils/rzp-utils';
import { isAmount } from 'common/utils/validators';
import analytics from './analytics';
import moment from 'moment';
import { FREQUENCY } from './constants';

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

// For UPI and Card hides attempt charge
export function isDomesticCardOrIsUPI(method, isDomesticMandate = false) {
  if (method === 'upi') {
    return true;
  }
  if (method === 'card' && isDomesticMandate) {
    return true;
  }
  return false;
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

export const isMonthlyDebitPattern = (frequency) => {
  return [
    FREQUENCY.MONTHLY,
    FREQUENCY.BIMONTHLY,
    FREQUENCY.QUARTERLY,
    FREQUENCY.HALF_YEARLY,
    FREQUENCY.YEARLY,
  ].includes(frequency);
};

export const getDebitPatternDesc = (frequency) => {
  let range = '1-31';
  if (frequency === FREQUENCY.WEEKLY) {
    range = '1-7';
  } else if (frequency === FREQUENCY.FORTNIGHTLY) {
    range = '1-15';
  }
  return `Enter a value between ${range} corresponding to days of a week`;
};

export const disablePastAndPostFortyYear = (date) => {
  if (!date) return false;
  const fortyYearsFromNow = moment(moment().add(40, 'y'), 'X');
  const today = moment();
  return fortyYearsFromNow.isBefore(date) || date.isBefore(today);
};
