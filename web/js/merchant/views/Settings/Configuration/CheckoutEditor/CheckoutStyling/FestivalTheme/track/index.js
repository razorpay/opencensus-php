import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'festival theme';

  return {
    toggleFestivalTheme: (visibility) => {
      sendToSegment('festival theme', 'toggle', { visibility }, section, subSection);
    },
  };
}

export default _track();
