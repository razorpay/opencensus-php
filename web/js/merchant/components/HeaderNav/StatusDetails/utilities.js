import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const getTimeinTwelveHourFormat = (dateObj) => {
  let hours = dateObj.getHours();
  let meridian = 'am';
  if (hours > 12) {
    hours = hours - 12;
    meridian = 'pm';
  }
  let minutes = dateObj.getMinutes();
  if (String(minutes).length === 1) {
    minutes = `0${minutes}`;
  }
  const time = `${hours}:${minutes} ${meridian}`;
  return time;
};

export const downtimeAnalyticsTrack = ({ objectName, method, ...restParams }) =>
  analyticsTrack({
    objectName,
    actionName: 'clicked',
    screen: 'Home page',
    properties: {
      itemName: 'Bank Downtimes',
      location: 'Top Navigation',
      method,
      ...restParams,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
