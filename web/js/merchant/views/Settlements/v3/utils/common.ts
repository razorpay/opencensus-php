import { DateInfo, ERROR_TYPE } from 'merchant/views/Settlements/v3/typings';
import moment from 'moment';
import { User } from 'common/typings';

export const getSettlementDate = (timestamp: number): DateInfo => {
  if (!timestamp) return { date: '---', time: '---' };
  const date = moment.unix(timestamp);
  return {
    date: `${date.format('ddd')} ${date.format('MMM')} ${date.format('DD')},`,
    time: date.format('LT'),
  };
};

export const getErrorType = (error: string): ERROR_TYPE => {
  if (
    error &&
    (error.includes('not a valid id') || error.includes('The id provided does not exist'))
  ) {
    return ERROR_TYPE.INVALID_ID;
  }
  return ERROR_TYPE.SERVER_ERROR;
};

export const validateUnixTimestamp = (timestamp: string): number | null => {
  const unixTime = parseInt(timestamp, 10);
  if (moment(unixTime).isValid()) {
    return unixTime;
  }
  return null;
};

export const showPaymentProviderColumn = (user: User): boolean =>
  user.isSingleReconEnabled && user.isOptimizerEnabled;
