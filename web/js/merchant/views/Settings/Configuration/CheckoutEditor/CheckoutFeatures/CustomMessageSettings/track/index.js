import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Features';
  const subSection = 'message banner';

  return {
    toggleMessageBanner: (visibility) => {
      sendToSegment('message banner', 'toggle', { visibility }, section, subSection);
    },
    handleMessageBannerTextInput: (input) => {
      sendToSegment('message banner text', 'input', { text_entered: input }, section, subSection);
    },
    handleMessageBannerThemeEdit: () => {
      sendToSegment('message banner theme edit', 'click', {}, section, subSection);
    },
  };
}

export default _track();
