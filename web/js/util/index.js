export const getFormattedAmount = amount =>
  (amount / 100)
    .toFixed(2)
    .replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,')
    .replace('.00', '');

export const deepClone = o => JSON.parse(JSON.stringify(o));
