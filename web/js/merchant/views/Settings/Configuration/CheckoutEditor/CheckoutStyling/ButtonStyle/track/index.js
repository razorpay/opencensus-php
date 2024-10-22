import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'border style';

  return {
    borderStyleClicked: (borderStyle) => {
      sendToSegment('border style', 'click', { borderStyle }, section, subSection);
    },
  };
}

export default _track();
