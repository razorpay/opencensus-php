import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { ProgramPolicy, ProgramPriceType } from 'merchant/views/GCMS/Programs/types';

export const daysToMonths = (days: number) => {
  return Math.floor(days / 30);
};

export const getProgramDenomination = ({ policy }: { policy: ProgramPolicy }) => {
  const sortedDenominations = Array.isArray(policy?.gift_card_price_denominations)
    ? policy.gift_card_price_denominations.sort((a, b) => a - b)
    : [];
  if (policy?.gift_card_price_type === ProgramPriceType.FIXED) {
    if (sortedDenominations.length > 1) {
      return `${getFormattedAmountNew(sortedDenominations[0], true)} - ${getFormattedAmountNew(
        sortedDenominations[sortedDenominations.length - 1],
        true,
      )}`;
    } else {
      return '-';
    }
  } else if (policy?.gift_card_maximum_price && policy?.gift_card_minimum_price) {
    return `${getFormattedAmountNew(
      policy.gift_card_minimum_price,
      true,
    )} - ${getFormattedAmountNew(policy.gift_card_maximum_price, true)}`;
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
