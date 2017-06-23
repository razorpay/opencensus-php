import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import StatusLabel from 'merchant/components/StatusLabel';

export const getAmount = key => item => (
  <Amount currency={item.currency || ''} value={item[key || 'amount']} />
);

export const amount = getAmount();
export const amountRefunded = getAmount('amount_refunded');
export const amountTransferred = getAmount('amount_transferred');

export const status = item => StatusLabel(item);
export const createdAt = item => (
  <Time value={item.created_at} format="DD MMM YYYY, hh:mm:ss a" />
);
