export const getFormattedAmount = amount =>
  (amount / 100)
    .toFixed(2)
    .replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,')
    .replace('.00', '');

export const deepClone = o => JSON.parse(JSON.stringify(o));
export const animObj = { enter: 300, exit: 300 };
export const prevent = e => {
  e.preventDefault();
  e.stopPropagation();
export const titleCase = (str = '') => {
  const chars = str.split('');

  return chars[0].toUpperCase() + chars.splice(1).join('');
};

export const snakeToTitleCase = (str = '') => {
  return str
    .split('_')
    .map(titleCase)
    .join(' ');
};
