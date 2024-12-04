import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import StatusLabel from 'merchant/components/StatusLabel';

// prettier-ignore
export const getAmount =
  (key = 'amount') =>
  (item) => {
    // Handles nested keys like 'item.amount'
    const value = key.split('.').reduce((prev, curr) => prev[curr], item);

    const currency = item.currency || (item.item ? item.item.currency : '');

    return <Amount currency={currency || 'INR'} value={value} />;
  };

// prettier-ignore
export const getTime =
  (key, format = 'DD MMM YYYY, hh:mm:ss a') =>
  (item) =>
    <Time value={item[key]} format={format} />;

export const amount = getAmount();
export const amountRefunded = getAmount('amount_refunded');
export const amountTransferred = getAmount('amount_transferred');

// eslint-disable-next-line babel/new-cap
export const status = (item) => StatusLabel(item);
export const createdAt = getTime('created_at');
export const createdAtShort = getTime('created_at', 'll');
export const createdAtTime = getTime('created_at', 'hh:mm a');

export const RRN = (item) => {
  return item?.acquirer_data ? item?.acquirer_data?.rrn : '-';
};
