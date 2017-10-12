import React from 'react';
const _getFormattedAmount = amount =>
  (amount / 100).toFixed(2).replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,');

const currencies = {
  INR: '₹',
  USD: 'US$',
};

export default ({ value, currency = 'INR', className, ...attrs }) => {
  const amount = _getFormattedAmount(value);

  return (
    <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
      {currencies[currency]} {amount.split('.')[0]}
      <span class="rzp-paise">.{amount.split('.')[1]}</span>
    </span>
  );
};
