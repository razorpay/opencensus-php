export const getFormattedAmount = amount =>
  (amount / 100).toFixed(2).replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,');

export const deepClone = o => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};
export const animObj = { enter: 300, exit: 300 };
export const prevent = e => {
  e.preventDefault();
  e.stopPropagation();
};

export const titleCase = (str = '--') => {
  const chars = str.split('');

  return chars[0].toUpperCase() + chars.splice(1).join('');
};

export const snakeToTitleCase = (str = '') => {
  return str
    .split('_')
    .map(titleCase)
    .join(' ');
};

/**
 * gets date in format 21st Dec, 2017 05:00
 * @param  {Number} unixTimestamp in seconds
 * @return {String}               date in 21st Dec, 2017 05:00 format
 */
export const formatDate = unixTimestamp =>
  unixTimestamp
    ? moment(unixTimestamp, 'X').format('Do MMM, YYYY hh:MM A')
    : null;

export const removeFromArray = (array, index) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

export const removeLineBreaks = str => str.replace(/[\n|\r]/g, ' ');

/*
 * Check for pending workflow requests
*/
export const isWorkflow = response => {
  if (
    typeof response.id !== 'undefined' &&
    response.id.indexOf('w_action') === 0 &&
    typeof response.workflow_id !== 'undefined'
  ) {
    const url = `/admin/requests/${response.id}`;
    return (location.href = url);
  } else {
    return false;
  }
};
