import { daysFromToday } from 'rzp/utils/rzp-utils';

export const daysLeftInExpiry = (expiresOn, prefixForDays = '') => {
  const daysLeft = daysFromToday(expiresOn);
  return daysLeft < 0
    ? 'Date Passed'
    : daysLeft === 0
      ? 'Today'
      : `${prefixForDays}${daysLeft} day${daysLeft > 1 ? 's' : ''}`;
};
