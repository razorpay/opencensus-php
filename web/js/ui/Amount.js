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
  const currencySymbolMapping =
    (window.currencyLib && window.currencyLib.displayCurrencies) || currencies;

  return (
    <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
      <span
        dangerouslySetInnerHTML={{ __html: currencySymbolMapping[currency] }}
      />
      {amount.split('.')[0]}
      <span class="rzp-paise">.{amount.split('.')[1]}</span>
    </span>
  );
};
