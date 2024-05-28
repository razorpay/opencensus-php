// Todo: delete this file, it's available in @dashboard/shared-ui
import React, { useCallback } from 'react';
import {
  getCurrencyList,
  getCurrencySymbol as i18nifyGetCurrencySymbol,
} from '@razorpay/i18nify-js/currency';

import { ANALYTICS } from 'common/constant';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import { getFormattedAmountByParts, classList } from 'common/utils/rzp-utils';
import sanitizer from 'common/utils/xss-sanitizer';
import useViewport, { ViewportProvider } from 'merchant/hooks/useViewPort';

const RTL_CURRENCIES = ['BHD', 'KWD', 'OMR'];

export function getCurrency(currencyISO) {
  return window.currencyList?.[currencyISO] || {};
}

export function getCurrencySymbol(currency = 'INR') {
  let currencySymbol = i18nifyGetCurrencySymbol(currency);

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

  const getDirection = useCallback(() => {
    if (RTL_CURRENCIES.includes(currency)) {
      return 'rtl';
    }
    return 'ltr';
  }, [currency]);

  let currencySymbol;

  try {
    currencySymbol = i18nifyGetCurrencySymbol(currency);
  } catch (error) {
    currencySymbol = currency;
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `currency: ${currency}`,
        error: `${error}`,
      },
    });
  }

  const amount = getFormattedAmountByParts(value, currency);
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
          dangerouslySetInnerHTML={{
            __html: `${amount?.minusSign || ''}${sanitizer(currencySymbol)}`,
          }}
        />{' '}
        <span className="rzp-whole">{amount?.integer}</span>
        {!hidePaisa && amount?.decimal && amount?.fraction && (
          <span className="rzp-paise">
            {amount.decimal}
            {amount.fraction}
          </span>
        )}
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
  let currencyInfo, currencySymbol, currencyName;

  try {
    currencyInfo = getCurrencyList()[currency];
    currencySymbol = currencyInfo.symbol;
    currencyName = currencyInfo.name;
  } catch (error) {
    currencySymbol = currency;
    currencyName = currency;
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `currency: ${currency}`,
        error: `${error}`,
      },
    });
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
