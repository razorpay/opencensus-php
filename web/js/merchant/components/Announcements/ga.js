import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Announcement';

export const track = setTrackData({
  eventCategory,
});

const events = {
  earlySettlementAppear: 'Early Settlement: Appear',
  earlySettlementClickRequestAccess: 'Early Settlement: Click - Request Access',
  earlySettlementClickCloseButton: 'Early Settlement: Click - Close Button',
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

export const trackRequestEarlySettlementModalSubmit = fromWhere => {
  if (fromWhere) {
    return setTrackData({
      eventCategory: 'Dashboard - Announcement',
      eventAction: 'Early Settlement: Modal Submit',
      eventLabel: fromWhere,
    })();
  } else {
    return setTrackData({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Request Early Settlement: Modal Submit',
    })();
  }
};

export const trackRequestEarlySettlementModalClose = fromWhere => {
  if (fromWhere) {
    return setTrackData({
      eventCategory: 'Dashboard - Announcement',
      eventAction: 'Early Settlement: Modal Close',
      eventLabel: fromWhere,
    })();
  } else {
    return setTrackData({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Request Early Settlement: Modal Close',
    })();
  }
};
