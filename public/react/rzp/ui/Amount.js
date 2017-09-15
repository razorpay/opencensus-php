import { getFormattedAmount } from 'rzp/utils/rzp-utils';

const currencies = {
  INR: '₹',
  USD: 'US$',
};

export default ({ value, currency = 'INR', className, ...attrs }) => {
  const amount = getFormattedAmount(value);
  return (
    <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
      {currencies[currency]} {amount.split('.')[0]}
      <span class="text-fade">.{amount.split('.')[1]}</span>
    </span>
  );
};
