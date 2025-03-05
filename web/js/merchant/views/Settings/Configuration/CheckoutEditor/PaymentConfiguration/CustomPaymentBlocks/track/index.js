import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Custom Block';

  return {
    createNewCustomBlockClicked: () => {
      sendToSegment('create new custom block', 'clicked', {}, section, subSection);
    },
    reorderCustomBlock: (oldOrder, newOrder) => {
      sendToSegment(
        'reorder custom block',
        'reordered',
        { oldOrder, newOrder },
        section,
        subSection,
      );
    },
    customPaymentBlockExpanded: (block) => {
      sendToSegment('custom payment block expand', 'clicked', { block }, section, subSection);
    },
    customPaymentBlockCollapsed: (block) => {
      sendToSegment('custom payment block collapse', 'clicked', { block }, section, subSection);
    },
    customPaymentBlockDeleted: (block) => {
      sendToSegment('custom payment block delete', 'clicked', { block }, section, subSection);
    },
    customPaymentBlockRenamed: (block) => {
      sendToSegment('custom payment block rename', 'input', { block }, section, subSection);
    },
    customPaymentBlockMethodReordered: (block, oldOrder, newOrder) => {
      sendToSegment(
        'custom payment block method reorder',
        'reordered',
        { block, oldOrder, newOrder },
        section,
        subSection,
      );
    },
    customPaymentBlockMethodVisibilityToggled: (block, methodDetails) => {
      sendToSegment(
        'custom payment block method visibility toggle',
        'clicked',
        { block, methodDetails },
        section,
        subSection,
      );
    },
    customPaymentBlockSingleInstrumentAdded: (instrument) => {
      sendToSegment(
        'custom payment block single instrument added',
        'clicked',
        { instrument },
        section,
        subSection,
      );
    },
    customPaymentBlockSingleInstrumentRemoved: (instrument) => {
      sendToSegment(
        'custom payment block single instrument removed',
        'clicked',
        { instrument },
        section,
        subSection,
      );
    },
    customPaymentBlockMethodClicked: (block) => {
      sendToSegment('custom payment block', 'clicked', { block }, section, subSection);
    },
  };
}

export default _track();
