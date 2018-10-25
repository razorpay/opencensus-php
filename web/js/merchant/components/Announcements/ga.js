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
  trackESPricingBack: [
    'Early Settlement Pricing: Back',
    'Request Early Settlement Pricing: Back',
  ],
  trackESPricingModalClose: [
    'Early Settlement Pricing: Modal Close',
    'Request Early Settlement Pricing: Modal Close',
  ],
  trackESSuccessModalClose: [
    'Early Settlement: Success Modal Close',
    'Request Early Settlement: Success Modal Close',
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
    trackers[elem] = function(eventLabel, eventValue = '') {
      if (eventLabel) {
        return setTrackData({
          eventCategory: 'Dashboard - Announcement',
          eventAction: commonEvents[elem][0],
          eventLabel: eventLabel,
          eventValue: eventValue,
        })();
      } else {
        return setTrackData({
          eventCategory: 'Dashboard - Settlements',
          eventAction: commonEvents[elem][1],
          eventValue: eventValue,
        })();
      }
    };
  });
  return trackers;
};

export default trackESAnnouncements();
