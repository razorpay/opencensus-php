import { getFormattedAmount } from 'rzp/utils/rzp-utils';

const currencies = {
  INR: '₹',
  USD: '$',
};

export default ({ value, currency, className, ...attrs }) => {
  return (
    <span class={`amount ${className}`} {...attrs}>
      {currencies[currency]} {getFormattedAmount(value)}
    </span>
  );
};
