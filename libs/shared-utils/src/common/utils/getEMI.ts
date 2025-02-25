/**
 * Calculates EMI (Equated Monthly Installment) for a given amount, interest rate, and number of months.
 *
 * @param {number} principle - The principal amount (in paise).
 * @param {number} length - The number of months for the loan.
 * @param {number} rate - The annual interest rate (percentage).
 * @returns {number} - The calculated EMI amount.
 *
 * @example
 * const emi = getEMI(500000, 12, 10); // EMI for 5,000 INR over 12 months with 10% interest
 * console.log(emi); // Output: EMI value
 */
export const getEMI = (principle: number, length: number, rate: number): number => {
  if (!rate) {
    return Math.ceil(principle / length);
  }

  rate /= 1200;

  const multiplier = (1 + rate) ** length;

  return Math.floor((principle * rate * multiplier) / (multiplier - 1));
};
