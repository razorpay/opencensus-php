const DATE_FORMAT = 'DD MMM YYYY';
import moment from 'moment';

export const getFormattedDate = unixTimeStamp =>
  moment(unixTimeStamp, 'X').format(DATE_FORMAT);
