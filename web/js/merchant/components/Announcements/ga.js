import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

const events = {
  earlySettlementAppear: 'Early Settlement: Appear',
  earlySettlementClickRequestAccess: 'Early Settlement: Click - Request Access',
  earlySettlementClickCloseButton: 'Early Settlement: Click - Close Button',
  earlySettlementModalSubmit: 'Early Settlement: Modal Submit',
  earlySettlementModalClose: 'Early Settlement: Modal Close',
};

const trackESAnnouncements = () => {
  let trackers = {};

  Object.keys(events).forEach(elem => {
    trackers[elem] = function(eventLabel) {
      track({
        eventAction: events[elem],
        eventLabel: eventLabel,
      });
    };
  });
  return trackers;
};

export default trackESAnnouncements();
