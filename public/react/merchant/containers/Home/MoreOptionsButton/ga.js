import track from 'merchant/containers/Home/ga';

const trackOverflowItem = (action, sectionTitle, tab) => {
  track({
    eventAction: action,
    eventLabel: `In ${sectionTitle}${tab ? ' | ' + tab : ''}`,
  });
};

export const trackOverflowDDClick = (sectionTitle, tab) =>
  trackOverflowItem('Click - Overflow Dropdown', sectionTitle, tab);

export const trackExportCSV = (sectionTitle, tab) =>
  trackOverflowItem('Export CSV', sectionTitle, tab);

export const trackDownloadImage = (sectionTitle, tab) =>
  trackOverflowItem('Download Image', sectionTitle, tab);
