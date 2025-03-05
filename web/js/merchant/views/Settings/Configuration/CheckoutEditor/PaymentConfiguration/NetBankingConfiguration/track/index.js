import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Netbanking Modal';

  return {
    bankVisibilityToggled: (bankCode, isChecked) => {
      sendToSegment(
        'bank visibility toggle',
        'clicked',
        { bankCode, isChecked },
        section,
        subSection,
      );
    },
  };
}

export default _track();
