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

/**
 * gets date in format 21st Dec, 2017 05:00
 * @param  {String/Number} value in date string or seconds
 * @return {String}               date in 21st Dec, 2017 05:00 format
 */
export const formatDate = value => {
  if (!value) {
    return null;
  }

  let date;

  /*
  * This is anomaly for shield service, which stored time in format = 2018-06-15T11:04:45Z.
  * It has to get fixed sometime. For now, it's exception.
  * */
  if (typeof value === 'string') {
    date = new moment(value);
  } else {
    date = moment.unix(value);
  }

  return date.format('Do MMM, YYYY hh:mm A');
};

export const removeFromArray = (array, index) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

export const removeLineBreaks = str => str.replace(/[\n|\r]/g, ' ');

/*
 * Check for pending workflow requests
*/
export const isWorkflow = (response, history = null) => {
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
 * Check for empty string/object
 * @param {*} value
 */
export function isBlank(value) {
  if (value !== null && typeof value === 'object') {
    return !Object.keys(value).length;
  }
  if (typeof value === 'string') {
    value = value.trim();
    return !value;
  }
  return isNone(value);
}

export function subString(str, length) {
  if (!str) {
    return str;
  }

  if (str.length > length) {
    return `${str.substr(0, length)} ...`;
  } else {
    return str;
  }
}

/*
* Helper fn. to fetch IFSC bank details for IFSC code entered in field
* */
export function getDetailsForIFSC(ifscCode) {
  if (ifscCode.length !== 11) {
    return null;
  }

  return axios('https://ifsc.razorpay.com/' + ifscCode).then(info => {
    info = info.data;

    if (info) {
      info = {
        Bank: info.BANK,
        Branch: info.BRANCH,
        City: info.CITY,
        State: info.STATE,
      };

      return info;
    }

    return null; // Invalid IFSC code
  });
}

/* Returns array with non-duplicate entries */
export function uniqueArray(arr) {
  if (!arr || !arr.length) {
    return;
  }

  const map = {};

  return arr.filter(item => !map[item] && (map[item] = true));
}

export function isMobileAndTablet() {
  let check = false;

  (function(a) {
    if (
      /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(
        a
      ) ||
      /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
        a.substr(0, 4)
      )
    )
      check = true;
  })(navigator.userAgent || navigator.vendor || window.opera);

  return check;
}
