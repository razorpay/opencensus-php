import { setTrackData } from 'common/utils/googleAnalytics';

const track = setTrackData({
  eventCategory: 'Dashboard - Support',
});

export const trackSupportButton = () => {
  track({
    eventAction: 'Click - Contact Support Main',
    eventLabel: 'opened',
  });
};

export const trackSupportOptions = (type) => {
  track({
    eventAction: 'Click - Support Options',
    eventLabel: type,
  });
};
