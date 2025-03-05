import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Paylater Modal';

  return {
    paylaterVisibilityToggled: (provider, isChecked) => {
      sendToSegment(
        'paylater visibility toggle',
        'clicked',
        { provider, isChecked },
        section,
        subSection,
      );
    },
  };
}

export default _track();
