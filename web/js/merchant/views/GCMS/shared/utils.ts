import { convertToMajorUnit } from '@razorpay/i18nify-js';
import { formatNumberByParts } from '@razorpay/i18nify-js/currency';

import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { ProgramPolicy, ProgramPriceType } from 'merchant/views/GCMS/Programs/types';

export const daysToMonths = (days: number) => {
  return Math.floor(days / 30);
};

export const formatAmountDenom = (amt, showCurrency, currency) => {
  try {
    const options: {
      intlOptions: {
        minimumFractionDigits: number;
      };
      currency?: string;
    } = {
      intlOptions: {
        minimumFractionDigits: 0,
      },
    };

    if (showCurrency) {
      options.currency = currency;
    }
    const byParts = formatNumberByParts(amt, options as any);
    return byParts.rawParts.reduce((acc, curr) => `${acc}${curr.value}`, '');
  } catch (e) {
    if (window.APP_ENV !== 'production') console.error(e);
    return showCurrency ? `${currency} ${amt}` : amt;
  }
};

export const getFormattedAmountNewDenom = (amount, showCurrency, currency = 'INR') => {
  let adjustedAmount;
  try {
    adjustedAmount = convertToMajorUnit(amount, { currency: currency as any }).toString();
  } catch (error) {
    adjustedAmount = (amount / 100).toFixed(2);
  }

  return formatAmountDenom(adjustedAmount, showCurrency, currency);
};

export const getProgramDenomination = ({ policy }: { policy: ProgramPolicy }) => {
  const sortedDenominations = Array.isArray(policy?.gift_card_price_denominations)
    ? policy.gift_card_price_denominations.sort((a, b) => a - b)
    : [];
  if (policy?.gift_card_price_type === ProgramPriceType.FIXED) {
    if (sortedDenominations.length > 1) {
      return `${getFormattedAmountNewDenom(
        sortedDenominations[0],
        true,
      )} - ${getFormattedAmountNewDenom(
        sortedDenominations[sortedDenominations.length - 1],
        true,
      )}`;
    } else {
      return '-';
    }
  } else if (policy?.gift_card_maximum_price && policy?.gift_card_minimum_price) {
    return `${getFormattedAmountNewDenom(
      policy.gift_card_minimum_price,
      true,
    )} - ${getFormattedAmountNewDenom(policy.gift_card_maximum_price, true)}`;
  } else {
    return 'Any';
  }
};

export const isGCMSExperimentEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { razorpay_gcms: undefined } };
  return isExperimentEnabled(abExperiments.razorpay_gcms);
};

export const isPositiveInteger = (value: string) => {
  return /^\d+$/.test(value);
};

/* convert unix timestamp to human readable short date format */
export const convertUnixToShortDate = (unixTimeStamp) => {
  const date = new Date(unixTimeStamp * 1000).toLocaleString('en-IN', {
    month: 'short',
    day: 'numeric',
    year: '2-digit',
  });
  return date;
};
