import { setTrackData } from 'rzp/utils/googleAnalytics';
import moment from 'moment';

const track = setTrackData({ eventCategory: 'Dashboard - Reports V2' });

export const trackDownload = (reportTitle, reportDesc) => {
  return track({
    eventAction: reportTitle,
    eventLabel: reportDesc,
  });
};

export const trackReportTabsClick = reportName => {
  return track({
    eventAction: 'Click - Report Tab',
    eventLabel: reportName,
  });
};

export const trackReportActions = (
  action,
  period,
  selectedDate,
  reportName
) => {
  const currDate = moment(new Date());
  const diffType = period === 'daily' ? 'days' : 'months';

  return track({
    eventAction: `Click - ${action}`,
    eventLabel: `${period} | ${reportName}`,
    eventValue: `${currDate.diff(selectedDate, diffType)}`,
  });
};

export const trackReportGenericActions = (
  action,
  label = null,
  value = null
) => {
  return track({
    eventAction: action,
    ...(label && { eventLabel: label }),
    ...(value && { eventValue: value }),
  });
};

export const trackTimeLapse = (action, timeLapse, label) => {
  let secondsLapse = Math.round(timeLapse / 1000);
  return track({
    eventAction: action,
    eventValue: secondsLapse,
    ...(label && { eventLabel: label }),
  });
};
