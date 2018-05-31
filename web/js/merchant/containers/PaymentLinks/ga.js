import defaultTrack, { setTrackData } from 'rzp/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'Dashboard - Announcement',
});

export const trackLinkClick = e =>
  track({
    eventAction: 'PLBU: Click - Link',
    eventLabel: e.target.innerText,
  });

/**
 * Track if PLBU announcement shown. (PLBU = Payment links Batch Upload)
 * @param {String} tab
 */
export const trackAnnouncementShown = tab =>
  track({
    eventAction: 'PLBU: Appear',
    eventLabel: '',
  });

/**
 * Track if PLBU announcement shown.
 * @param {String} tab
 */
export const trackCloseAnnouncement = tab =>
  track({
    eventAction: 'PLBU: Click - Close Button',
    eventLabel: '',
  });

/* ABOVE EVENTS ARE FOR 'Dashboard - Announcements' category */

export const trackHelpClick = e =>
  defaultTrack({
    eventCategory: 'Dashboard - Payment Links',
    eventAction: "Click - Create Partial Payment - What's This",
    eventLabel: 'From PL Create Modal',
  });
