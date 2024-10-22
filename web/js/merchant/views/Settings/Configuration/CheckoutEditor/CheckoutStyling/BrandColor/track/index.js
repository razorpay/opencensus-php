import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'background color';

  return {
    backgroundColorChange: () => {
      sendToSegment('background color edit', 'click', {}, section, subSection);
    },
  };
}

export default _track();
