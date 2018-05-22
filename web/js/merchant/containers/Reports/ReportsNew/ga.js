import { setTrackData } from 'rzp/utils/googleAnalytics';

const pageTitle = 'Dashboard - Reports',
  pageTitle_v2 = 'Dashboard - Reports V2',
  track = setTrackData({ eventCategory: pageTitle }),
  track_v2 = setTrackData({ eventCategory: pageTitle_v2 });

export const trackDownload = (reportTitle, reportDesc) => {
  return track({
    eventAction: reportTitle,
    eventLabel: reportDesc,
  });
};

export const trackReportTabsClick = reportName => {
  return track_v2({
    eventAction: 'Click - Report Tab',
    eventLabel: reportName,
  });
};

export const trackReportActions = (action, period, timeVal, reportName) => {
  return track_v2({
    eventAction: `Click - ${action}`,
    eventLabel: `${period} | ${reportName}`,
    eventValue: `${timeVal}`,
  });
};

export const trackReportGenericActions = (
  action,
  label = null,
  value = null
) => {
  return track_v2({
    eventAction: action,
    ...(label && { eventLabel: label }),
    ...(value && { eventValue: value }),
  });
};

export const trackTimeLapse = (action, timeLapse) => {
  return track_v2({
    eventAction: action,
    eventValue: (timeLapse / 1000).toFixed(2),
  });
};
