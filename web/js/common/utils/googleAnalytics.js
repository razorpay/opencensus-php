const track = ({ eventCategory, eventAction, eventLabel, eventValue }) => {
  if (!window.rzpAnalytics) {
    return;
  }

  window.rzpAnalytics?.({
    eventCategory,
    eventAction,
    eventLabel,
    eventValue,
  });
};

const setTrackData = (fixedData = {}) => {
  /*
   * same as track but you can pre-set certain values
   * Eg. in home page, eventCategory is always `Dashboard - Home`
   */

  return (data = {}) => {
    return track({ ...data, ...fixedData });
  };
};

export { setTrackData };
export default track;

export const trackFb = (event) => {
  window.rzpAnalytics?.({
    name: 'facebook',
    event,
  });
};

export const trackhubsContactUpdate = (data) => {
  if (window.trackHubs) {
    window.trackHubs({
      name: 'update_property',
      data,
    });
  }
};

/**
 * Common function to fire all events at once.
 * @param {Object} - fbData, bingData, liData(linkedin), twiData(twitter), quoraData(Quora), redditData(Reddit)
 *
 * Check static dir for to understand track method format.
 * Refer https://docs.google.com/spreadsheets/d/1VIdNTDbvocP11Fltjhk55aHIgpYchUeCJgwnqElW3vY/edit#gid=0 for the values of specific events.
 */
export const fireAnalyticsEvents = ({ ...data }) => {
  if (data.fbData) {
    window.rzpAnalytics?.({
      name: 'facebook',
      event: data.fbData,
    });
  }
  if (data.bingData) {
    window.rzpAnalytics?.({
      name: 'bing',
      event: data.bingData,
    });
  }
  if (data.liData) {
    const event = {};
    event.conversionId = data.liData;
    window.rzpAnalytics?.({
      name: 'linkedIn',
      value: event,
    });
  }
  if (data.quoraData) {
    window.rzpAnalytics?.({
      name: 'quora',
      event: data.quoraData,
    });
  }
  if (data.redditData) {
    window.rzpAnalytics?.({
      name: 'reddit',
      event: data.redditData,
    });
  }
  if (data.twiData) {
    const event = {};
    event.txn_id = data.twiData;
    window.rzpAnalytics?.({
      name: 'twitter',
      value: event,
    });
  }
};
