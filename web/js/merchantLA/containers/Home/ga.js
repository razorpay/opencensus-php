import { setTrackData } from 'rzp/utils/googleAnalytics';

const pageTitle = 'Dashboard - Home V2';

export const track = setTrackData({ eventCategory: pageTitle });

export const trackError = error => {
  const eventLabel =
    typeof error === 'object' ? JSON.stringify(error) : String(error);

  track({
    eventAction: 'Error Triggered',
    eventLabel,
  });
};

export const trackPresetChange = preset => {
  track({
    eventAction: 'Select - Date Dropdown',
    eventLabel: preset.name,
    eventValue: preset.value,
  });
};

export const trackDatesChange = (from, to) => {
  const seconds = to.unix() - from.unix(),
    fromDateString = from.toLocaleString(),
    toDateString = to.toLocaleString();

  track({
    eventAction: 'Select - Start Date',
    eventLabel: fromDateString,
  });

  track({
    eventAction: 'Select - End Date',
    eventLabel: toDateString,
  });

  track({
    eventAction: 'Select - Date Range',
    eventLabel: `${fromDateString} - ${toDateString}`,
    eventValue: seconds,
  });
};

export const trackGoToLinks = (toText, fromText) => {
  track({
    eventAction: `Go to - ${toText}`,
    eventLabel: `From ${fromText}`,
  });
};

export const trackSettlementsClick = () => {
  trackGoToLinks('Settlements', pageTitle);
};

export const trackPlatformAnalyticsHidden = percent => {
  track({
    eventAction: 'Data Hide - Platform',
    eventLabel: `${percent}%`,
    eventValue: percent,
  });
};

export const trackForceOldDashboard = () => {
  track({
    eventAction: 'Force Old Dashboard on Mobile',
    eventLabel: `Resolution - ${window.outerWidth}x${window.outerHeight}`,
  });
};

export const trackNoData = description => {
  track({
    eventAction: 'No Data Found',
    eventLabel: description,
  });
};

export const trackViewTour = () => {
  track({
    eventAction: 'Click - View Tour on Banner',
  });
};

export default track;
