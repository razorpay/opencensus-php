const track = ({ eventCategory, eventAction, eventLabel, eventValue }) => {
  if (!window.rzpAnalytics) {
    return;
  }

  window.rzpAnalytics({
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
