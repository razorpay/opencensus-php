import { daysFromToday } from 'rzp/utils/rzp-utils';

export const daysLeftInExpiry = (expiresOn, prefixForDays = '') => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <span class="text-muted">Passed</span>;
  } else if (daysLeft === 0) {
    return <strong class="text-danger">Today</strong>;
  } else {
    return `${prefixForDays}${daysLeft} day${daysLeft > 1 ? 's' : ''}`;
  }
};
