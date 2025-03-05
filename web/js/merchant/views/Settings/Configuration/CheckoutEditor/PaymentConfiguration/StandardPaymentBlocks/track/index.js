import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Standard Blocks';

  return {
    showAllClicked: () => {
      sendToSegment('showAll', 'clicked', {}, section, subSection);
    },
    hideSomeClicked: () => {
      sendToSegment('hide some', 'clicked', {}, section, subSection);
    },
    enableAllClicked: () => {
      sendToSegment('enable all', 'clicked', {}, section, subSection);
    },
    hideAllClicked: () => {
      sendToSegment('hide all', 'clicked', {}, section, subSection);
    },
    toggleStandardBlockVisibility: (block) => {
      sendToSegment('toggle standard block visibility', 'clicked', { block }, section, subSection);
    },
    reorderStandardBlocks: (oldOrder, newOrder) => {
      sendToSegment(
        'reorder standard blocks',
        'reordered',
        { oldOrder, newOrder },
        section,
        subSection,
      );
    },
    standardBlockClicked: (blockName) => {
      sendToSegment('standard block', 'clicked', { blockName }, section, subSection);
    },
  };
}

export default _track();
