import { setTrackData } from 'common/utils/googleAnalytics';

export default eventCategory => {
  const track = setTrackData({ eventCategory });

  return {
    trackToggleHistory: typeOfCredit => view => {
      track({
        eventAction: `${view ? 'View' : 'Hide'} History - ${typeOfCredit}`,
      });
    },
    trackOpenDetails: _ => {
      track({
        eventAction: 'Open details - Refunds',
      });
    },
    trackCloseDetails: _ => {
      track({
        eventAction: 'Close details - Refunds',
      });
    },
    trackSearchAnalytics: eventLabel => {
      track({
        eventAction: 'Search - Refunds',
        eventLabel,
      });
    },
    trackClearAnalytics: _ => {
      track({
        eventAction: 'Clear Search Params - Search params',
      });
    },
  };
};
