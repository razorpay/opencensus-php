import { setTrackData } from 'common/utils/googleAnalytics';

export const EVENT_CATEGORY_DASHBOARD_SETTLEMENTS = 'Dashboard - Settlements';

export const EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT = 'Dashboard - Early Settlement';

export const track = setTrackData({
  eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
});

export const trackES = setTrackData({
  eventCategory: EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
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

  Object.keys(ondemandEvents).forEach((elem) => {
    trackers[elem] = function (eventLabel) {
      track({
        eventAction: ondemandEvents[elem],
        eventLabel: eventLabel,
      });
    };
  });
  return trackers;
};

export const trackOndemand = ondemandTrackers();
