import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'font style';

  return {
    fontChange: (font) => {
      sendToSegment('font selected', 'select', { font }, section, subSection);
    },
  };
}

export default _track();
