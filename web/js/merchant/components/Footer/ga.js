import { setTrackData } from 'rzp/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'Dashboard - Footer',
});

export const trackLinkClick = e =>
  track({
    eventAction: e.target.innerText,
  });
