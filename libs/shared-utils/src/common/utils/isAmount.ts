/**
 * Checks if the provided value is a valid amount.
 * The amount can have up to two decimal places.
 * 
 * @param {string | number} amount - The amount to validate.
 * @returns {boolean} - Returns true if the amount is valid, false otherwise.
 */
export const isAmount = (amount: string | number): boolean => {
  amount = amount || '';
  const amountRegExp = /^\d+(\.\d{1,2})?$/;
  return amountRegExp.test(String(amount));
};
