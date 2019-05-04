import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { getFormattedAmount } from 'rzp/utils/rzp-utils';
import { classList } from 'common/util';

const currencies = {
  INR: '₹',
  USD: 'US$',
  SGD: 'S$',
  EUR: '€',
};

export default ({
  value,
  currency = 'INR',
  className,
  parentQuerySelector,
  ...attrs
}) => {
  const amount = getFormattedAmount(value);
  let currencySymbol = currencies[currency];

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
  }

  // TODO: pointer-events: allow, but cursor be as per inherit
  return (
    <AmountTooltip
      currency={currency}
      parentQuerySelector={parentQuerySelector}
    >
      <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
        <span
          class="rzp-currency"
          dangerouslySetInnerHTML={{ __html: currencySymbol }}
        />{' '}
        <span class="rzp-whole">{amount.split('.')[0]}</span>
        <span class="rzp-paise">.{amount.split('.')[1]}</span>
      </span>
    </AmountTooltip>
  );
};

// Get the currencySymbolMapping from user.getCurrencyList
export function AmountTooltip({
  children,
  currency,
  customClass,
  parentQuerySelector,
}) {
  const currencySymbolMapping = currencies;
  let currencySymbol = currencies[currency];

  if (window.currencyList && window.currencyList[currency]) {
    currencySymbol = window.currencyList[currency].symbol;
  }

  return (
    <span
      className={classList('help-content help-content--currency', customClass)}
    >
      {children || <span>{currencySymbolMapping[currency]}</span>}
      <Popover
        align="top"
        theme="dark"
        parentQuerySelector={parentQuerySelector}
      >
        <PopoverBody>
          <div style={{ textAlign: 'center' }}>
            {currencySymbol} - {window.currencyList[currency].name} ({currency})
          </div>
        </PopoverBody>
      </Popover>
    </span>
  );
}
