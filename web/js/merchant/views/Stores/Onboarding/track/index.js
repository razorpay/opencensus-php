import { triggerHotjarRecording } from 'common/utils/hotjar';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let config = {
    store_id: '',
  };

  function sendToSegment(objectName, actionName = 'click', properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'onboarding stores',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        ...config,
      },
    });
  }

  function setConfig(newConfig) {
    config = {
      ...config,
      ...newConfig,
    };
  }

  return {
    open: () => {
      triggerHotjarRecording('Store_Creation');
    },
    init(_config) {
      setConfig(_config);
    },
    saveAndProceedBtn: () => sendToSegment('create store step1'),
    createStoreBtn: () => sendToSegment('create store step2'),
    addProductPopupBtn: () => sendToSegment('add product popup'),
    addProductDashboardBtn: () => sendToSegment('add product dashboard'),
    storeLinkClick: () => sendToSegment('slug dashboard'),
  };
}

export default _track();
