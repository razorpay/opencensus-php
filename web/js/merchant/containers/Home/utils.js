import moment from 'moment';

export const IsOutsideDateRangeForHPAnalytics = (date) => {
  const ninetyDaysAgo = moment().subtract(90, 'days');
  return date.isBefore(ninetyDaysAgo, 'day') || moment().isBefore(date);
};
