import { setTrackData } from 'rzp/utils/googleAnalytics';

export default eventCategory => {
  const track = setTrackData({ eventCategory });

  return {
    trackForm: typeOfCredit => action => {
      track({
        eventAction: `${action} Form - Manage Alerts - ${typeOfCredit}`,
      });
    },

    trackToggleHistory: typeOfCredit => view => {
      track({
        eventAction: `${view ? 'View' : 'Hide'} History - ${typeOfCredit}`,
      });
    },
  };
};
