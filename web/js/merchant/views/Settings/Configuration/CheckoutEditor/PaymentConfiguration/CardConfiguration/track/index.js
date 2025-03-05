import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Card Modal';

  return {
    cardTypeToggled: (type, isChecked) => {
      sendToSegment('card type toggle', 'clicked', { type, isChecked }, section, subSection);
    },
    cardProviderToggled: (provider, isChecked) => {
      sendToSegment(
        'card provider toggle',
        'clicked',
        { provider, isChecked },
        section,
        subSection,
      );
    },
    cardIssuerToggled: (issuer, isChecked) => {
      sendToSegment('card issuer toggle', 'clicked', { issuer, isChecked }, section, subSection);
    },
    cardBinNumberAdded: (binNumber) => {
      sendToSegment('card bin number added', 'input', { binNumber }, section, subSection);
    },
  };
}

export default _track();
