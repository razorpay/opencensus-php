import { getFormattedAmount } from 'rzp/utils/rzp-utils';

const currencies = {
  INR: '₹',
  USD: '$',
};

export default ({ value, currency, className, ...attrs }) => {
  const amount = getFormattedAmount(value);
  return (
    <span class={`amount ${className}`} {...attrs}>
      {currencies[currency]} {amount.split('.')[0]}
      <span class="text-fade">.{amount.split('.')[1]}</span>
    </span>
  );
};
