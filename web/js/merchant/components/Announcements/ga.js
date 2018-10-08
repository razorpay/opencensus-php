import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

const bannerEvents = {
  earlySettlementAppear: 'Early Settlement: Appear',
  earlySettlementClickRequestAccess: 'Early Settlement: Click - Request Access',
  earlySettlementClickCloseButton: 'Early Settlement: Click - Close Button',
};

const commonEvents = {
  trackESModalSubmit: [
    'Early Settlement: Modal Submit',
    'Request Early Settlement: Modal Submit',
  ],
  trackESModalClose: [
    'Early Settlement: Modal Close',
    'Request Early Settlement: Modal Close',
  ],
  trackESPricingAccept: [
    'Early Settlement Pricing: Accept Pricing',
    'Request Early Settlement Pricing: Accept Pricing',
  ],
  trackESPricingCancel: [
    'Early Settlement Pricing: Cancel',
    'Request Early Settlement Pricing: Cancel',
  ],
  trackESPricingModalClose: [
    'Early Settlement Pricing: Modal Close',
    'Request Early Settlement Pricing: Modal Close',
  ],
};

const trackESAnnouncements = () => {
  let trackers = {};

  Object.keys(bannerEvents).forEach(elem => {
    trackers[elem] = function(eventLabel) {
      track({
        eventAction: bannerEvents[elem],
        eventLabel: eventLabel,
      });
    };
  });

  /**
   * Creating different trackers for events triggered
   * from banner and static "Request Early Settlements" button
   */
  Object.keys(commonEvents).forEach(elem => {
    trackers[elem] = function(eventLabel) {
      if (eventLabel) {
        return setTrackData({
          eventCategory: 'Dashboard - Announcement',
          eventAction: commonEvents[elem][0],
          eventLabel: eventLabel,
        })();
      } else {
        return setTrackData({
          eventCategory: 'Dashboard - Settlements',
          eventAction: commonEvents[elem][1],
        })();
      }
    };
  });
  return trackers;
};

export default trackESAnnouncements();
