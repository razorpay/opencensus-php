import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'RTB';

  return {
    toggleRTBVisibility: (visibility) => {
      sendToSegment('RTB visibility', 'toggle', { visibility }, section, subSection);
    },
    logRTBConfigAPIResponse: (rtb_enabled) => {
      sendToSegment('RTB Response', 'render', { rtb_enabled }, section, subSection);
    },
  };
}

export default _track();
