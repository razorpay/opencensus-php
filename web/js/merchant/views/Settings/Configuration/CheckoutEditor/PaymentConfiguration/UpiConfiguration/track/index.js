import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'UPI Modal';

  return {
    upiFlowToggled: (flow, isChecked) => {
      sendToSegment('UPI config toggle', 'clicked', { flow, isChecked }, section, subSection);
    },
  };
}

export default _track();
