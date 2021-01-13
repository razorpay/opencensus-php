import { MIN_TIME_TO_REFRESH } from './components/data';
import { getAttachmentExpiryTime } from 'common/utils/rzp-utils';
import moment from 'moment';

export function getExpiryTime(awsURL) {
  const expiryTime = getAttachmentExpiryTime(awsURL, 2, 'h');

  // Return time in milliseconds
  return expiryTime.diff(moment(), 'ms');
}
