export const prevent = e => {
  e.preventDefault();
  e.stopPropagation();
};

export const titleCase = str => {
  if (!str) {
    // to handle empty string or null values
    str = '--';
  }
  const chars = str.split('');

  return chars[0].toUpperCase() + chars.splice(1).join('');
};

export const snakeToTitleCase = (str = '') => {
  return str
    .split('_')
    .map(titleCase)
    .join(' ');
};

export function classList(...args) {
  const classes = [];

  for (var i = 0; i < args.length; i++) {
    if (args[i]) {
      if (args[i] instanceof Array) {
        args[i] = args[i].join(' ');
      }

      classes.push(args[i]);
    }
  }

  return classes.join(' ');
}

/**
 * @param {String}
 * Find experiment value if it exists
 */
export function getExperiment(name) {
  if (window.rzp_user) {
    let experiment = (window.rzp_user.experiments || {})[name] || {};

    return experiment.result;
  }

  return null;
}

/*
*
*
*
****************************************************************************************

Don't add anything more in the file. Check rzp/utils/rzp-utils OR razorx/helpers/utils

****************************************************************************************
*
*
*
*/
