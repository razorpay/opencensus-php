import { setTrackData } from 'common/utils/googleAnalytics';
import { EVENT_CATEGORY_DASHBOARD_SETTLEMENTS } from 'merchant/views/Settlements/Settlements/ga';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

const commonEvents = {
  trackESModalSubmit: ['Early Settlement: Modal Submit', 'Request Early Settlement: Modal Submit'],
  trackESModalClose: ['Early Settlement: Modal Close', 'Request Early Settlement: Modal Close'],
  trackESPricingAccept: [
    'Early Settlement Pricing: Accept Pricing',
    'Request Early Settlement Pricing: Accept Pricing',
  ],
  trackESPricingBack: ['Early Settlement Pricing: Back', 'Request Early Settlement Pricing: Back'],
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

  /**
   * Creating different trackers for events triggered
   * from banner and static "Request Early Settlements" button
   */
  Object.keys(commonEvents).forEach((elem) => {
    trackers[elem] = function (eventLabel, eventValue = '') {
      if (eventLabel) {
        return setTrackData({
          eventCategory: 'Dashboard - Announcement',
          eventAction: commonEvents[elem][0],
          eventLabel: eventLabel,
          eventValue: eventValue,
        })();
      } else {
        return setTrackData({
          eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
          eventAction: commonEvents[elem][1],
          eventValue: eventValue,
        })();
      }
    };
  });
  return trackers;
};

export default trackESAnnouncements();

export const trackInstantSettlementsBanner = (label) => {
  track({
    eventAction: 'Instant Settlements - Banner',
    eventLabel: label,
  });
};

export const trackMarketingExperimentBanner = (action, label, value) => {
  track({
    eventAction: `${action} - Banner`,
    eventLabel: label,
    eventValue: value || '',
  });
};
