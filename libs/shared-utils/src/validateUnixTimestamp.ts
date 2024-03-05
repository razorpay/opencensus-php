import moment from 'moment';

export const validateUnixTimestamp = (timestamp: string): number | null => {
  const unixTime = parseInt(timestamp, 10);
  if (moment(unixTime).isValid()) {
    return unixTime;
  }
  return null;
};
