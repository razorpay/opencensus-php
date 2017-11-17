const MONTHS = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

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

export const formatDate = unixTimestamp => {
  var date = new Date(1e3 * unixTimestamp);

  var dateSuffix = 'th';
  var dateOfMonth = date.getDate();
  dateSuffix =
    [0, 'st', 'nd', 'rd'][dateOfMonth === 31 ? 1 : dateOfMonth % 20] ||
    dateSuffix;

  return `${date.getDate()}${dateSuffix} ${
    MONTHS[date.getMonth()]
  }, ${date.getFullYear()} ${date.getHours()}:${date.getMinutes()}`;
};

export const removeFromArray = (array, index) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

export const removeLineBreaks = str => str.replace(/[\n|\r]/g, ' ');

/*
 * Check for pending workflow requests
 * Pass withRouter 'history' prop from component
 * Fallback to window.open if not a called from react component
*/
export const isWorkflow = (response, history = null) => {
  if (
    typeof response.id !== 'undefined' &&
    response.id.indexOf('w_action') === 0 &&
    typeof response.workflow_id !== 'undefined'
  ) {
    const workflowUrl = `/requests/${response.id}`;
    if (history) {
      history.push(workflowUrl);
    } else {
      window.open(workflowUrl);
    }
  }

  return false;
};
