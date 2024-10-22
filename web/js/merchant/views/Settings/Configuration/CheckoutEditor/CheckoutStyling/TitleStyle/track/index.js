import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'title style';

  return {
    editTitleStyle: () => {
      sendToSegment('title style edit', 'click', {}, section, subSection);
    },
    selectTitleStyle: (selectedStyle) => {
      sendToSegment(
        'title style continue to upload',
        'click',
        { selectedStyle },
        section,
        subSection,
      );
    },
    saveTitleStyle: (titleStylePayload) => {
      sendToSegment('title style save', 'click', { titleStylePayload }, section, subSection);
    },
    discardTitleStyle: (selectedStyle) => {
      sendToSegment('title style discard', 'click', { selectedStyle }, section, subSection);
    },
  };
}

export default _track();
