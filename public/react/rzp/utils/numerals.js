const suffixes = ['k', 'L', 'Cr'];

export const formatNumberWithCommas = value =>
  Number(value).toLocaleString('en-IN', {
    currency: 'INR',
  });

export const humanReadableIndian = (num, noOfVisibleDigits = 3) => {
  const maxWithoutReadable = Math.pow(10, Math.max(noOfVisibleDigits, 3));
  if (num < Math.pow(10, Math.max(noOfVisibleDigits, 3)))
    return formatNumberWithCommas(num);

  const formattedNumberArr = formatNumberWithCommas(num).split(',');
  const suffix = suffixes[Math.min(2, formattedNumberArr.length - 2)] || '';
  return `${Number(`${formattedNumberArr[0]}.${formattedNumberArr[1]}`).toFixed(
    2
  )}${suffix}`;
};
