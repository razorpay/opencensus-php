/**
 * Determines the current financial year based on the current date.
 * 
 * In many countries like India, the financial year starts in April (month 4).
 * This function calculates the financial year, which spans from April 1st of the previous year 
 * to March 31st of the current year if the current month is between January and March.
 * 
 * @returns {number} - The starting year of the current financial year.
 * 
 * @example
 * const financialYear = getCurrentFinancialYear();
 * console.log(financialYear); // Output: 2023 (if the current date is in 2024 before April)
 */
export const getCurrentFinancialYear = (): number => {
  const today = new Date();
  const currentMonth = today.getMonth() + 1; // getMonth is zero-indexed
  if (currentMonth <= 3) {
    return today.getFullYear() - 1;
  } else {
    return today.getFullYear();
  }
};
