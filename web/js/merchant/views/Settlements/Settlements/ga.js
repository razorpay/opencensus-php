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
  const trackers = {};

  Object.keys(ondemandEvents).forEach((elem) => {
    trackers[elem] = function fn(eventLabel) {
      track({
        eventAction: ondemandEvents[elem],
        eventLabel,
      });
    };
  });
  return trackers;
};

export const trackOndemand = ondemandTrackers();

const trackGAEvents = (data) => {
  window.rzpAnalytics?.({
    eventCategory: 'Day 1 ES',
    ...data,
  });
};

export const trackAnimatedSettleBtnImpressions = (MID, source) => {
  trackGAEvents({
    eventAction: `Animated CTA Impression`,
    eventLabel: `ES Animated CTA | source - ${source} | seen by - ${MID} `,
  });
};

export const trackAnimatedSettleBtnClick = (MID, source) => {
  trackGAEvents({
    eventAction: `Animated CTA Clicks`,
    eventLabel: `ES Animated CTA | source - ${source} | clicked by - ${MID}`,
  });
};

export const trackAnimatedSettleBtnClickType = (MID, source, type) => {
  trackGAEvents({
    eventAction: `Animated CTA Clicks`,
    eventLabel: `ES Animated CTA | source - ${source} | type - ${
      type ? 'es_restricted' : 'ondemand'
    } | clicked by - ${MID}`,
  });
};

export const trackModalOpen = (MID) => {
  trackGAEvents({
    eventAction: `ES Modal Open`,
    eventLabel: `ES Settlement | Initiated | ${MID}`,
  });
};

export const trackEsInfoHover = () => {
  trackGAEvents({
    eventAction: `ES Restricted info icon hover`,
    eventLabel: `ES Settlement | Seen tooltip`,
  });
};

export const trackEsAmountUpdated = () => {
  trackGAEvents({
    eventAction: `ES Amount Updated`,
    eventLabel: `ES Settlement | Update Prefilled Amount`,
  });
};

export const trackEsAmountError = () => {
  trackGAEvents({
    eventAction: `ES Amount Error`,
    eventLabel: `ES Settlement | Seen Error`,
  });
};

export const trackEsShowBreakup = (before = false) => {
  trackGAEvents({
    eventAction: `ES Show breakup ${before ? 'before confirm' : 'after settlement'} `,
    eventLabel: `ES Settlement | Seen Breakup | ${before ? 'Before' : 'After'} `,
  });
};

export const trackEsConfirm = (MID) => {
  trackGAEvents({
    eventAction: `Click CTA - Confirm`,
    eventLabel: `ES Settlement | First Confirm | ${MID}`,
  });
};

export const trackEsSettlementAction = (MID, source, confirm = false) => {
  trackGAEvents({
    eventAction: `Click CTA - ${confirm ? 'Yes, Settle' : "No, Don't"} `,
    eventLabel: `ES Settlement | ${
      confirm ? 'Second Confirm' : 'Cancel Second Confirm'
    }  | Source  - ${source} | ${MID}`,
  });
};

export const trackEsModalCloseIconChurn = (MID) => {
  trackGAEvents({
    eventAction: `Click CTA Icon - Close`,
    eventLabel: `ES Settlement | Merchant Churn | ${MID}`,
  });
};

export const trackEsModalCloseAction = (MID, confirm = false) => {
  trackGAEvents({
    eventAction: `Click CTA - ${confirm ? 'Confirm & Close' : 'Go back'}`,
    eventLabel: `ES Settlement | Merchant Churn${confirm ? ` | ${MID}` : ''}`,
  });
};

export const trackEsChurnReason = (MID, reason) => {
  trackGAEvents({
    eventAction: `ES Churn Reason`,
    eventLabel: `ES Settlement | ${MID} | ${reason}`,
  });
};

export const trackEsModalCloseCTA = () => {
  trackGAEvents({
    eventAction: `Click CTA - Close`,
    eventLabel: `ES Settlement | CTA Close`,
  });
};

export const trackEsModalCloseIcon = () => {
  trackGAEvents({
    eventAction: `Click CTA Icon - Close`,
    eventLabel: `ES Settlement | CTA Icon Close`,
  });
};
