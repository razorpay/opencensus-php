import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Checkout Styling';
  const subSection = 'sidebar graphic';

  return {
    toggleSidebarGraphic: (visibility) => {
      sendToSegment('sidebar graphic', 'toggle', { visibility }, section, subSection);
    },
    selectSidebarGraphicImage: (image) => {
      sendToSegment('sidebar graphic image', 'click', { image }, section, subSection);
    },
  };
}

export default _track();
