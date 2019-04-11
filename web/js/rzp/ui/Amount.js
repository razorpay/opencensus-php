import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { getFormattedAmount } from 'rzp/utils/rzp-utils';
import { classList } from 'common/util';

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
    <AmountTooltip currency={currency}>
      <span class={`rzp-amount ${className ? className : ''}`} {...attrs}>
        <span
          dangerouslySetInnerHTML={{ __html: currencySymbolMapping[currency] }}
        />
        {amount.split('.')[0]}
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
  const currencySymbolMapping =
    (window.currencyLib && window.currencyLib.displayCurrencies) || currencies;

  return (
    <small className={classList('help-content', customClass)}>
      {children || <span>{currencySymbolMapping[currency]}</span>}
      <Popover
        align="top"
        theme="dark"
        parentQuerySelector={parentQuerySelector}
      >
        <PopoverBody>
          <div>// TODO: Description base {currency}</div>
        </PopoverBody>
      </Popover>
    </small>
  );
}
