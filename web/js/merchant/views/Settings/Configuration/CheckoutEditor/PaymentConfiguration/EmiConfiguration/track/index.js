import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'EMI Modal';

  return {
    cardTypeToggled: (type, isChecked) => {
      sendToSegment('emi card type toggle', 'clicked', { type, isChecked }, section, subSection);
    },
    cardProviderToggled: (provider, isChecked) => {
      sendToSegment(
        'emi card provider toggle',
        'clicked',
        { provider, isChecked },
        section,
        subSection,
      );
    },
    cardIssuerToggled: (issuer, isChecked) => {
      sendToSegment(
        'emi card issuer toggle',
        'clicked',
        { issuer, isChecked },
        section,
        subSection,
      );
    },
    cardBinNumberAdded: (binNumber) => {
      sendToSegment('emi card bin number added', 'input', { binNumber }, section, subSection);
    },
    cardlessEMIToggled: (provider, isChecked) => {
      sendToSegment('cardless EMI toggle', 'clicked', { provider, isChecked }, section, subSection);
    },
  };
}

export default _track();
