import { getFormattedNumber } from 'rzp/utils/rzp-utils';
const suffixes = ['k', 'L', 'Cr'];

export const formatNumberWithCommas = value =>
  getFormattedNumber(Number(value));

const HundredCr = 1000000000;
const ThousandCr = HundredCr * 10;
export const humanReadableIndian = (num, noOfVisibleDigits = 3) => {
  if (num < Math.pow(10, Math.max(noOfVisibleDigits, 3)))
    return formatNumberWithCommas(num);

  if (num >= HundredCr) {
    const numOfDecimals = num >= ThousandCr ? 0 : 1;
    return `${(num / (HundredCr / 100)).toFixed(numOfDecimals)}Cr`;
  }

  const formattedNumberArr = formatNumberWithCommas(num.toFixed()).split(',');
  const suffix = suffixes[Math.min(2, formattedNumberArr.length - 2)] || '',
    hasDecimals = Number(formattedNumberArr[1]) !== 0;

  // TODO: need to restructure this logic
  return `${Number(
    `${formattedNumberArr[0]}${hasDecimals ? '.' + formattedNumberArr[1] : ''}`
  )[hasDecimals ? 'toFixed' : 'toString'](hasDecimals ? 2 : 10)}${suffix}`;
};

export const humanReadableIndianCurrency = (...args) =>
  '₹' + humanReadableIndian(...args);
