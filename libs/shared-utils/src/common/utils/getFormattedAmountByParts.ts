import {
  convertToMajorUnit,
  formatNumberByParts,
  CurrencyCodeType,
} from '@razorpay/i18nify-js/currency';

/**
 * Parses the amount string and returns the parsed object with integer, decimal, fraction, currency, etc.
 *
 * @param {string | number} amount - The amount to format.
 * @param {string} [currency='INR'] - The currency code for formatting.
 * @returns {ReturnType<typeof formatNumberByParts>} - An object with formatted amount parts including integer, decimal, and fraction.
 *
 * @example
 * const result = getFormattedAmountByParts(123456.789, 'USD');
 * console.log(result);
 * // Output: { integer: "123", decimal: ".", fraction: "46", isPrefixSymbol: true, currency: "$" }
 */
export const getFormattedAmountByParts = (
  amount: number,
  currency: CurrencyCodeType = 'INR',
): ReturnType<typeof formatNumberByParts> => {
  let updatedAmount: string,
    integer: string,
    fraction: string,
    byParts: ReturnType<typeof formatNumberByParts>;

  try {
    updatedAmount = convertToMajorUnit(amount, { currency }).toString();
    integer = updatedAmount.split('.')[0] || '';
    fraction = updatedAmount.split('.')[1] || '';

    byParts = formatNumberByParts(updatedAmount, {
      currency,
      intlOptions: {
        style: 'currency',
      },
    });
  } catch (error) {
    updatedAmount = (Number(amount) / 100).toFixed(2);
    integer = updatedAmount.split('.')[0] || '';
    fraction = updatedAmount.split('.')[1] || '';

    const formattedObj = {
      integer: Math.abs(Number(integer)),
      decimal: '.',
      fraction,
      currency,
      isPrefixSymbol: true,
      minusSign: Number(integer) < 0 ? '-' : '',
    };

    // @ts-ignore
    byParts = {
      ...formattedObj,
      rawParts: [
        { type: 'currency', value: formattedObj.currency },
        { type: 'literal', value: ' ' },
        { type: 'integer', value: formattedObj.integer },
        { type: 'decimal', value: formattedObj.decimal },
        { type: 'fraction', value: formattedObj.fraction },
      ],
    };

    if (formattedObj.minusSign === '-')
      byParts.rawParts = [
        { type: 'minusSign', value: formattedObj.minusSign },
        ...byParts.rawParts,
      ];
  }

  return byParts;
};
