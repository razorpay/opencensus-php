import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Settlements';

export const track = setTrackData({
  eventCategory,
});

export function trackEarlySettlementRequests() {
  track({
    eventAction: `Click - Request Early Settlements`,
  });
}

export function trackHowSettlementsWorkClicks() {
  track({
    eventAction: `Click - How Settlements Work`,
  });
}
