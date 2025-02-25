import moment from 'moment';
import { getURLQueryParams } from './getURLQueryParams';

moment.updateLocale('en', {
  relativeTime: {
    s: 'few secs',
    ss: '%s secs',
    m: 'a min',
    mm: '%d mins',
  },
});

/**
 * Calculates the expiry time of an AWS signed URL.
 *
 * @param {string} awsURL - AWS signed URL to calculate expiry for.
 * @param {string} defaultUnit - Default minimum time in unit if there is any error in URL parsing.
 * @param {moment.unitOfTime.DurationConstructor} UNIT_TYPE - Unit type (e.g., 'hours', 'minutes').
 * @returns {moment.Moment} - Moment object representing the expiry time.
 *
 * @example
 * const awsURL = 'https://example.com?X-Amz-Date=20220101T120000Z&X-Amz-Expires=3600';
 * const expiryTime = getAttachmentExpiryTime(awsURL, '2', 'hours');
 * console.log(expiryTime); // Outputs the calculated expiry time.
 */
export function getAttachmentExpiryTime(
  awsURL: string,
  defaultUnit: string,
  UNIT_TYPE: moment.unitOfTime.DurationConstructor,
): moment.Moment {
  try {
    const params = getURLQueryParams(awsURL);
    const dateTimeStr = params['X-Amz-Date'];
    const offsetTime = parseInt(params['X-Amz-Expires'] || '', 10);

    if (!dateTimeStr || isNaN(offsetTime)) {
      throw new Error('Invalid AWS URL');
    }

    return moment.utc(dateTimeStr).add(offsetTime, 's');
  } catch (ex) {
    // return default time of 2 hours if parsing fails
    return moment().add(defaultUnit, UNIT_TYPE);
  }
}
