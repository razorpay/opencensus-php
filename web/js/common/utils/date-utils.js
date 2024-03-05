// Todo: delete this file, it's available in @dashboard/shared-utils
import moment from 'moment';

export const getCurrentMonth = () => {
  const today = new Date();
  return today.getMonth();
};

export const getCurrentUnixTime = () => {
  return moment().unix();
};

export const getCurrentYear = () => {
  return moment().year();
};

export const getYear = (timestamp) => {
  return moment.unix(timestamp).year();
};

export const isAfterDate = (timestamp, afterDate) => {
  return moment.unix(timestamp).isAfter(afterDate);
};

export const monthsMap = [
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

export const getDateFormat = (timestamp) => {
  // previous year format
  let format = 'MMM D, YYYY, h:mma';
  const currentYear = getCurrentYear();
  const createdAtYear = getYear(timestamp);
  if (createdAtYear === currentYear) {
    // current year format
    format = 'MMM D, h:mma';
    const tendDaysBefore = moment().subtract(10, 'days');
    if (isAfterDate(timestamp, tendDaysBefore)) {
      // last 10 days format
      format = 'ddd MMM D, h:mma';
    }
  }
  return format;
};
