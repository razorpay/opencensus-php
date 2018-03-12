import { setTrackData } from 'rzp/utils/googleAnalytics';

const pageTitle = 'Dashboard - Reports',
      track     = setTrackData({eventCategory: pageTitle});

export const trackDownload = (reportTitle, reportDesc) => {

  return track({
    eventAction: reportTitle,
    eventLabel: reportDesc
  });
}
