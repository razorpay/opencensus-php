import moment from 'moment';
const DATE_FORMAT = 'DD MMM YYYY';

export const getFormattedDate = (unixTimeStamp) => moment(unixTimeStamp, 'X').format(DATE_FORMAT);

export const getTimeUnix = (timeMoment) =>
  moment(timeMoment)
    .clone()
    .startOf('minute')
    .diff(moment(timeMoment).clone().startOf('day'), 'seconds');

export const getStartAndEndUnixTimeStampsForDaysFrom = (
  numberOfDays = 0,
  dateInMoment = moment().subtract(1, 'day'),
) => {
  const lastDayEndOfDayUnix = dateInMoment.endOf('day').format('X');
  const lastNthStartOfDayUnix = dateInMoment
    .subtract(Math.max(numberOfDays - 1, 0), 'day')
    .startOf('day')
    .format('X');

  return [Number(lastNthStartOfDayUnix), Number(lastDayEndOfDayUnix)];
};

export const extractExtensionFromTemplate = (template) =>
  ((template || {}).file_meta || {}).extension;

const logProcessingStatuses = ['created', 'processing'];
export const isLogInProgress = (logStatus) => logProcessingStatuses.includes(logStatus);

export const getActualLogStatus = ({ status, fileId }) => {
  switch (status) {
    case 'created':
    case 'processing':
    case 'retrying':
      return 'in-process';
    case 'processed':
      return fileId ? 'ready-for-download' : 'no-data';
    case 'failed':
      return 'error';
    default:
      return null;
  }
};
