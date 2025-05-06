import moment from 'moment';

export const getTimeAgo = (timestamp: number | string) => {
  const time = typeof timestamp === 'string' ? parseInt(timestamp, 10) : timestamp;
  const now = moment();
  const givenTime = moment(time * 1000);

  const diffInSeconds = now.diff(givenTime, 'seconds');
  const diffInMinutes = now.diff(givenTime, 'minutes');
  const diffInHours = now.diff(givenTime, 'hours');
  const diffInDays = now.diff(givenTime, 'days');
  const diffInWeeks = now.diff(givenTime, 'weeks');
  const diffInMonths = now.diff(givenTime, 'months');
  const diffInYears = now.diff(givenTime, 'years');

  const formatUnit = (count: number, unit: string) => {
    const label = count === 1 ? unit : `${unit}s`;
    return `Updated ${count} ${label} ago`;
  };

  if (diffInSeconds <= 0) {
    return 'Updated now';
  } else if (diffInSeconds < 60) {
    return formatUnit(diffInSeconds, 'second');
  } else if (diffInMinutes < 60) {
    return formatUnit(diffInMinutes, 'minute');
  } else if (diffInHours < 24) {
    return formatUnit(diffInHours, 'hour');
  } else if (diffInDays < 7) {
    return formatUnit(diffInDays, 'day');
  } else if (diffInWeeks < 4) {
    return formatUnit(diffInWeeks, 'week');
  } else if (diffInMonths < 12) {
    return formatUnit(diffInMonths, 'month');
  } else {
    return formatUnit(diffInYears, 'year');
  }
};
