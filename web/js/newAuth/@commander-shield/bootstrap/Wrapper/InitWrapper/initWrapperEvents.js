import trackEvents from '../../../js/analytics';

const initWrapperEvents = {
  trackShieldLoad: () => {
    // global init event common for all ie signup/signin/any_other_place
    trackEvents.prometheus({ type: 'shield_init', label: 'shield_loaded', isShieldEvent: true });
  },
};

export default initWrapperEvents;
