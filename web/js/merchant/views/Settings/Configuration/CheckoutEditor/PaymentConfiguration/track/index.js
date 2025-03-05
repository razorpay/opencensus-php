import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Configuration List';

  return {
    paymentConfigVisited: () => {
      sendToSegment('payment configuration visited', 'loaded', {}, section, subSection);
    },
    paymentConfigsLoaded: (paymentConfigIDs = []) => {
      sendToSegment(
        'payment configurations loaded',
        'loaded',
        paymentConfigIDs,
        section,
        subSection,
      );
    },
    setupGuideClicked: () => {
      sendToSegment('setup guide', 'clicked', {}, section, subSection);
    },
    paymentConfigEditClicked: (configID) => {
      sendToSegment('payment configuration edit', 'clicked', { configID }, section, subSection);
    },
    createNewPaymentConfigClicked: () => {
      sendToSegment('create new payment configuration', 'clicked', {}, section, subSection);
    },
  };
}

export default _track();
