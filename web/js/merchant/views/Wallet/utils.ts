export const getFormattedCurrency = (amount = 0, currency = 'INR'): string => {
  amount = amount / 100;
  return amount.toLocaleString('en-IN', {
    maximumFractionDigits: 2,
    style: 'currency',
    currency,
  });
};
