/**
 * This function takes currency as argument and returns true if currency is 3 decimal
 * @param {*} currency currency to check
 * @returns {Boolean} true is currency is 3 decimal
 */
export const isCurrencyThreeDecimal = (currency) => {
  const currencies = ['KWD', 'OMR', 'BHD'];
  return currencies.includes(currency);
};

/**
 * Replace dot with comma.
 * Useful for making currencies which use comma
 * as the decimal point.
 * @param {String} str
 *
 * @return {String}
 */
const makeDecimalComma = (str, comma = ',') => str.replace(/\./, comma);

/**
 * formats amount with commas for INR
 * @param {number} amount
 * @param {number} decimals
 * @returns {string}
 * eg, 123456.00 => 1,23,456.00
 */
const inrCommaFormatter = (amount, decimals) => {
  return String(amount).replace(new RegExp(`(.{1,2})(?=.(..)+(\\..{${decimals}})$)`, 'g'), '$1,');
};

export const CURRENCY_FORMATTERS = {
  // #,###.##
  three: (amount, decimals) => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1,',
    );
    return amountStr;
  },

  // #.###,##
  threecommadecimal: (amount, decimals) => {
    const amountStr = makeDecimalComma(String(amount)).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\,.{${decimals}})$)`, 'g'),
      '$1.',
    );
    return amountStr;
  },

  // # ###.##
  threespaceseparator: (amount, decimals) => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1 ',
    );
    return amountStr;
  },

  // # ###,##
  threespacecommadecimal: (amount, decimals) => {
    const amountStr = makeDecimalComma(String(amount)).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\,.{${decimals}})$)`, 'g'),
      '$1 ',
    );
    return amountStr;
  },

  // #, ###.##
  szl: (amount, decimals) => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1, ',
    );
    return amountStr;
  },

  // #'###.##
  chf: (amount, decimals) => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      "$1'",
    );
    return amountStr;
  },

  // 	#,##,###.##
  inr: (amount, decimals) => {
    const amountStr = inrCommaFormatter(amount, decimals);
    return amountStr;
  },

  // #,###.## | #,###,###.## | ###,###,###.##
  myr: (amount, decimals) => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1,',
    );
    return amountStr;
  },

  none: (amount) => String(amount),
};
