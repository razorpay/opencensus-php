import { setTrackData } from 'rzp/utils/googleAnalytics';

export default eventCategory => {
  const track = setTrackData({ eventCategory });

  return {
    trackToggleHistory: typeOfCredit => view => {
      track({
        eventAction: `${view ? 'View' : 'Hide'} History - ${typeOfCredit}`,
      });
    },
  };
};
