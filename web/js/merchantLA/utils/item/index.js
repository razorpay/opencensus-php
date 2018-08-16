import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import StatusLabel from 'merchant/components/StatusLabel';

export const getAmount = (key = 'amount') => item => {
  // Handles nested keys like 'item.amount'
  let value = key.split('.').reduce((prev, curr) => prev[curr], item);

  return <Amount currency={item.currency || ''} value={value} />;
};

export const getTime = (key, format = 'DD MMM YYYY, hh:mm:ss a') => item => (
  <Time value={item[key]} format={format} />
);

export const amount = getAmount();
export const amountTransferred = getAmount('amount_transferred');

export const status = item => StatusLabel(item);
export const createdAt = getTime('created_at');
