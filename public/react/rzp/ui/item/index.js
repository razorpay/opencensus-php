import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import Time from 'rzp/ui/Time';
import StatusLabel from 'merchant/components/StatusLabel';

const currencies = {
  INR: '₹',
  USD: '$',
};

const getCurrency = item => (currencies[item.currency] || '') + ' ';
const getAmount = key => item =>
  getCurrency(item) + getFixedINRAmount(item[key || 'amount']);

export const amount = getAmount();
export const amountRefunded = getAmount('amount_refunded');
export const amountTransferred = getAmount('amount_transferred');

export const status = item => StatusLabel(item);
export const createdAt = item => (
  <Time value={item.created_at} format="DD MMM YYYY, hh:mm:ss a" />
);
