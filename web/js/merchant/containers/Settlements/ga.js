import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Settlements';

export const track = setTrackData({
  eventCategory,
});

export const trackES = setTrackData({
  eventCategory: 'Dashboard - Early Settlement',
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

const ondemandEvents = {
  trackSettleNow: 'Click - Settle Now',
  trackEnterAmount: 'Click - Enter Amount',
  trackSettleEarly: 'Click - Settle Early',
  trackBankHolidays: 'Click - Bank Holidays',
  trackCloseModal: 'Click - Close Modal',
  trackAmounTooHigh: 'Appear - Amount too high error',
  trackCloseButton: 'Click - Close Button (Success Screen)',
  trackSuccessCloseModal: 'Click - Close Modal (Success Screen)',
};

const ondemandTrackers = () => {
  let trackers = {};

  Object.keys(ondemandEvents).forEach(elem => {
    trackers[elem] = function(eventLabel) {
      track({
        eventAction: ondemandEvents[elem],
        eventLabel: eventLabel,
      });
    };
  });
  return trackers;
};

export const trackOndemand = ondemandTrackers();
