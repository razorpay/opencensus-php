import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmount, classList } from 'common/utils/rzp-utils';

const currencies = {
  INR: {
    name: 'Indian Rupee',
    symbol: '₹',
  },
  USD: {
    name: 'US Dollar',
    symbol: '$',
  },
  SGD: {
    name: 'Singapore Dollar',
    symbol: 'S$',
  },
  EUR: {
    name: 'Euro',
    symbol: '€',
  },
};

export function getCurrency(currencyISO) {
  return window.currencyList[currencyISO] || {};
}

export default ({
  value,
  currency = 'INR',
  className,
  parentQuerySelector,
  hidePaisa = false,
  ...attrs
}) => {
  if (!currency) {
    currency = 'INR';
  }

  const amount = getFormattedAmount(value);

  let currencySymbol = currencies[currency] ? currencies[currency].symbol : currency;

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
  }

  // TODO: pointer-events: allow, but cursor be as per inherit
  return (
    <AmountTooltip currency={currency} parentQuerySelector={parentQuerySelector}>
      <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
        <span class="rzp-currency" dangerouslySetInnerHTML={{ __html: currencySymbol }} />{' '}
        <span class="rzp-whole">{amount.split('.')[0]}</span>
        {!hidePaisa && <span class="rzp-paise">.{amount.split('.')[1]}</span>}
      </span>
    </AmountTooltip>
  );
};

// Get the currencySymbolMapping from user.getCurrencyList
export function AmountTooltip({ children, currency = 'INR', customClass, parentQuerySelector }) {
  if (!currency) {
    currency = 'INR';
  }

  let currencySymbol = currencies[currency] ? currencies[currency].symbol : currency;

  let currencyName = currencySymbol;

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
    currencyName = window.currencyList[currency].name;
  }

  return (
    <span className={classList('help-content help-content--currency', customClass)}>
      {children || <span>{currencySymbol}</span>}
      <Popover align="top" theme="dark" parentQuerySelector={parentQuerySelector}>
        <PopoverBody>
          <div style={{ textAlign: 'center' }}>
            {currencySymbol} - {currencyName} ({currency})
          </div>
        </PopoverBody>
      </Popover>
    </span>
  );
}
