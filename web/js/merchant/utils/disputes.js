import { daysFromToday } from 'rzp/utils/rzp-utils';

export const daysLeftInExpiry = (expiresOn, prefixForDays = '') => {
  const daysLeft = daysFromToday(expiresOn);
  return daysLeft < 0 ? (
    <span class="text-muted">Passed</span>
  ) : daysLeft === 0 ? (
    <strong class="text-danger">Today</strong>
  ) : (
    `${prefixForDays}${daysLeft} day${daysLeft > 1 ? 's' : ''}`
  );
};
