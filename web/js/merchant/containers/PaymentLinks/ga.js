import defaultTrack, { setTrackData } from 'rzp/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'Dashboard - Announcement',
});

/* ABOVE EVENTS ARE FOR 'Dashboard - Announcements' category */

export const trackHelpClick = e =>
  defaultTrack({
    eventCategory: 'Dashboard - Payment Links',
    eventAction: "Click - Create Partial Payment - What's This",
    eventLabel: 'From PL Create Modal',
  });
