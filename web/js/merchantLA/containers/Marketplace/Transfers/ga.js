import { setTrackData } from 'rzp/utils/googleAnalytics';

export default eventCategory => {
  const track = setTrackData({ eventCategory });

  return {
    trackOpenDetails: _ => {
      track({
        eventAction: 'Open details - Transfers',
      });
    },
    trackCloseDetails: _ => {
      track({
        eventAction: 'Close details - Transfers',
      });
    },
  };
};
