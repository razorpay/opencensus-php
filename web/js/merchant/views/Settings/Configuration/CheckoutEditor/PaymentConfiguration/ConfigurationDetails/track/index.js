import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

function _track() {
  const section = 'Payment Configuration';
  const subSection = 'Configuration Details';

  return {
    goBackButtonClicked: () => {
      sendToSegment('go back', 'clicked', {}, section, subSection);
    },
    renameConfigurationClicked: () => {
      sendToSegment('rename configuration', 'clicked', {}, section, subSection);
    },
    configurationRenamed: (updatedName) => {
      sendToSegment('configuration renamed', 'click', { updatedName }, section, subSection);
    },
    saveAsDefaultClicked: () => {
      sendToSegment('save as default', 'clicked', {}, section, subSection);
    },
    viewSetupGuideClicked: () => {
      sendToSegment('view setup guide', 'clicked', {}, section, subSection);
    },
    discardChangesContinueClicked: () => {
      sendToSegment('discard changes continue on go back', 'clicked', {}, section, subSection);
    },
  };
}

export default _track();
