const DATE_FORMAT = 'DD MMM YYYY';
import moment from 'moment';

export const getFormattedDate = unixTimeStamp =>
  moment(unixTimeStamp, 'X').format(DATE_FORMAT);

export const getTimeUnix = timeMoment =>
  timeMoment.diff(timeMoment.clone().startOf('day'), 'seconds');

export const getStartAndEndUnixTimeStampsForDaysFrom = (
  numberOfDays = 0,
  dateInMoment = moment().subtract(1, 'day')
) => {
  const lastDayEndOfDayUnix = dateInMoment.endOf('day').format('X');
  const lastNthStartOfDayUnix = dateInMoment
    .subtract(Math.max(numberOfDays - 1, 0), 'day')
    .startOf('day')
    .format('X');

  return [lastNthStartOfDayUnix, lastDayEndOfDayUnix];
};

export const extractExtensionFromTemplate = template =>
  ((template || {}).file_meta || {}).extension;

const logProcessingStatuses = ['created', 'processing'];
export const isLogInProgress = logStatus =>
  logProcessingStatuses.includes(logStatus);

export const getActualLogStatus = ({ status, fileId }) => {
  if (status === 'created') return status;

  if (status === 'processed' && !fileId) return 'no-data';

  if (status === 'processed') return 'ready-for-download';

  if (status === 'failed') return 'error';
};
