import React, { useCallback } from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmount, classList } from 'common/utils/rzp-utils';
import useViewport, { ViewportProvider } from 'merchant/hooks/useViewPort';
import sanitizer from 'common/utils/xss-sanitizer';

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
  MYR: {
    name: 'Malaysian Ringgit',
    symbol: 'RM',
  },
};

const RTL_CURRENCIES = ['BHD', 'KWD', 'OMR'];

export function getCurrency(currencyISO) {
  return window.currencyList?.[currencyISO] || {};
}

export function getCurrencySymbol(currency) {
  let currencySymbol = currencies[currency] ? currencies[currency].symbol : currency;

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
  }

  return currencySymbol;
}

const Amount = ({
  value,
  currency = 'INR',
  className,
  parentQuerySelector,
  // eslint-disable-next-line no-unused-vars
  hidePaisa = false,
  testId = null,
  ...attrs
}) => {
  if (!currency) {
    currency = 'INR';
  }

  if (testId !== null) {
    attrs['data-testid'] = testId;
  }
  const amount = getFormattedAmount(value, currency);

  const currencySymbol = getCurrencySymbol(currency);

  const getDirection = useCallback(() => {
    if (RTL_CURRENCIES.includes(currency)) {
      return 'rtl';
    }
    return 'ltr';
  }, [currency]);

  // TODO: pointer-events: allow, but cursor be as per inherit
  return (
    <AmountTooltip currency={currency} parentQuerySelector={parentQuerySelector}>
      <span
        className={`rzp-amount ${className ? className : ''}`}
        dir={getDirection()}
        aria-label="amount-info"
        {...attrs}
      >
        <span
          className="rzp-currency"
          dangerouslySetInnerHTML={{ __html: sanitizer(currencySymbol) }}
        />{' '}
        <span className="rzp-whole">{amount?.split('.')[0]}</span>
        {!hidePaisa && <span className="rzp-paise">.{amount?.split('.')[1]}</span>}
      </span>
    </AmountTooltip>
  );
};

export default Amount;

// prettier-ignore
const wrapper =
  (Component) =>
  ({ ...props }) => {
    return (
      <ViewportProvider>
        <Component {...props} />
      </ViewportProvider>
    );
  };

export const AmountTooltip = wrapper(AmountTooltipContainer);

// Get the currencySymbolMapping from user.getCurrencyList
export function AmountTooltipContainer({
  children,
  currency = 'INR',
  customClass,
  parentQuerySelector,
}) {
  if (!currency) {
    currency = 'INR';
  }

  const context = useViewport();

  let currencySymbol = currencies[currency] ? currencies[currency].symbol : currency;

  let currencyName = currencySymbol;

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
    currencyName = window.currencyList[currency].name;
  }

  return (
    <span className={classList('help-content help-content--currency', customClass)}>
      {children || <span>{currencySymbol}</span>}
      {context !== 'PHONE' && (
        <Popover align="top" theme="dark" parentQuerySelector={parentQuerySelector}>
          <PopoverBody>
            <div style={{ textAlign: 'center' }}>
              {currencySymbol} - {currencyName} ({currency})
            </div>
          </PopoverBody>
        </Popover>
      )}
    </span>
  );
}
