import { getCurrencySymbol } from 'common/ui/Amount';
import { getFormattedNumber } from 'common/utils/rzp-utils';
const suffixes = ['k', 'L', 'Cr'];

export const formatNumberWithCommas = (value) => getFormattedNumber(Number(value));

const HundredCr = 1000000000;
const ThousandCr = HundredCr * 10;
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
