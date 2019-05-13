import React from 'react';
import { getFormattedAmount } from 'common/util';

const currencies = {
  INR: '₹',
  USD: 'US$',
  SGD: 'S$',
  EUR: '€',
};

export default ({ value, currency = 'INR', className, ...attrs }) => {
  const amount = getFormattedAmount(value);
  const currencySymbol = currencies[currency] || currency;

  return (
    <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
      {currencySymbol} {amount.split('.')[0]}
      <span class="rzp-paise">.{amount.split('.')[1]}</span>
    </span>
  );
};
