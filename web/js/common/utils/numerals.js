import { formatNumberByParts } from '@razorpay/i18nify-js/currency';

import { getCurrencySymbol } from 'common/ui/Amount';
import { getFormattedNumber } from 'common/utils/rzp-utils';
import { ANALYTICS } from 'common/constant';
import { analyticsTrack } from 'common/utils/analytics';
const suffixes = ['k', 'L', 'Cr'];

export const formatNumberWithCommas = (value) => getFormattedNumber(Number(value));

const HundredCr = 1000000000;
const ThousandCr = HundredCr * 10;

export const i18nifyHumanReadable = (num, currencyCode) => {
  try {
    const options = {
      intlOptions: {
        notation: 'compact',
        minimumFractionDigits: 2,
        trailingZeroDisplay: 'stripIfInteger',
      },
    };
    if (currencyCode) {
      options.intlOptions.currency = currencyCode;
    }

    const formattedAmountObj = formatNumberByParts(num, options);
    const formattedAmount = formattedAmountObj.rawParts.reduce((acc, p) => acc + p.value, '');

    return formattedAmount;
  } catch (error) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `${currencyCode} ${num}`,
        error: `${error}`,
      },
    });
    return currencyCode ? `${getCurrencySymbol(currencyCode) || currencyCode} ${num}` : num;
  }
};

export const humanReadableIndian = (num, noOfVisibleDigits = 3) => {
  // eslint-disable-next-line prefer-exponentiation-operator
  if (num < Math.pow(10, Math.max(noOfVisibleDigits, 3))) return formatNumberWithCommas(num);

  if (num >= HundredCr) {
    const numOfDecimals = num >= ThousandCr ? 0 : 1;
    return `${(num / (HundredCr / 100)).toFixed(numOfDecimals)}Cr`;
  }

  const formattedNumberArr = formatNumberWithCommas(num.toFixed()).split(',');
  const suffix = suffixes[Math.min(2, formattedNumberArr.length - 2)] || '';
  const hasDecimals = Number(formattedNumberArr[1]) !== 0;

  // TODO: need to restructure this logic
  return `${Number(`${formattedNumberArr[0]}${hasDecimals ? `.${formattedNumberArr[1]}` : ''}`)[
    hasDecimals ? 'toFixed' : 'toString'
  ](hasDecimals ? 2 : 10)}${suffix}`;
};

export const humanReadableIndianCurrency = (...args) => `₹${humanReadableIndian(...args)}`;

/*
 * @params {value} number
 */
const i18Suffixes = ['', 'k', 'm', 'b', 't'];
export const i18HumanReadableNumerals = (value, currencyCode) => {
  if (currencyCode === 'INR') {
    return humanReadableIndian(value);
  }

  // Remove decimal values
  value = value.toFixed(0);
  let newValue = value;

  if (value >= 1000) {
    // Identifies the index of i18Suffixes
    const suffixNum = Math.floor(`${value}`.length / 3);

    // Shorting the value
    let shortValue = '';
    for (let precision = 3; precision >= 1; precision--) {
      if (suffixNum != 0) {
        shortValue = parseFloat(value / 1000 ** suffixNum);
      } else {
        shortValue = parseFloat(value);
      }

      const dotLessShortValue = `${shortValue}`.replace(/[^a-zA-Z 0-9]+/g, '');
      if (dotLessShortValue.length <= 2) {
        break;
      }
    }

    if (shortValue % 1 != 0) {
      shortValue = Number(shortValue).toFixed(2);
    }

    newValue = shortValue + i18Suffixes[suffixNum];
  }

  return newValue;
};

/*
 * @params {currency} number
 * @params {amount} number
 */
export const i18HumanReadableCurrency = (amount, currency) => {
  const currencySymbol = getCurrencySymbol(currency);
  if (currency === 'INR') {
    return `${currencySymbol} ${humanReadableIndian(amount)}`;
  }

  return `${currencySymbol} ${i18HumanReadableNumerals(amount, currency)}`;
};

/**
 * Formats a numeric value into a string representation with appropriate localization and fractional precision.
 *
 * This function aims to format a given number into a string that adheres to internationalization standards,
 * ensuring a consistent display of numbers across various locales.
 */
export const getI18nifyFormattedNumber = (value) => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  let formattedAmountObj;

  try {
    formattedAmountObj = formatNumberByParts(value, {
      intlOptions: {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2,
        trailingZeroDisplay: 'stripIfInteger',
      },
    });
  } catch (e) {
    return value;
  }

  return formattedAmountObj.fraction
    ? `${formattedAmountObj.integer}${formattedAmountObj.decimal}${formattedAmountObj.fraction}`
    : formattedAmountObj.integer;
};
