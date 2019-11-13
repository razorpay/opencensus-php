import { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

export function trackLoad(unreadAnnouncements) {
  track({
    eventAction: 'Announcement Appear',
    eventLabel: String(unreadAnnouncements),
  });
}

export function trackExpand(unreadAnnouncements) {
  track({
    eventAction: 'Announcement Click',
    eventLabel: String(unreadAnnouncements),
  });
}

export function trackAnnouncement(announcementTitle, label) {
  track({
    eventAction: announcementTitle,
    eventLabel: label,
  });
}
