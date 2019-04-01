import { getFormattedAmount } from 'rzp/utils/rzp-utils';

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
      {currencySymbolMapping[currency]} {amount.split('.')[0]}
      <span class="rzp-paise">.{amount.split('.')[1]}</span>
    </span>
  );
};
