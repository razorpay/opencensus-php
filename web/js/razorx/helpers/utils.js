/* eslint-disable babel/new-cap */
import moment from 'moment';

// eslint-disable-next-line consistent-return
export const deepClone = (o) => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};

/**
 * gets date in format 21st Dec, 2017 05:00
 * @param  {String/Number} value in date string or seconds
 * @return {String}               date in 21st Dec, 2017 05:00 format
 */
export const formatDate = (value) => {
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

export const formatEpochDate = (epoch) => {
  return moment.unix(epoch).format('Do MMM, YYYY hh:mm A');
};
