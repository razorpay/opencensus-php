export const getFormattedAmount = amount =>
  (amount / 100)
    .toFixed(2)
    .replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,')
    .replace('.00', '');
