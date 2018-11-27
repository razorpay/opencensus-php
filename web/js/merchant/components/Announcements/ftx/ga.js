import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

export function visitBanner() {
  track({
    eventAction: `Dashboard - FTX`,
    eventLabel: 'appear-ftx-2018',
  });
}

export function passLink() {
  track({
    eventAction: `Dashboard - FTX`,
    eventLabel: 'pass-link-2018',
  });
}

export function eventsLink() {
  track({
    eventAction: `Dashboard - FTX`,
    eventLabel: 'events-page-2018',
  });
}
