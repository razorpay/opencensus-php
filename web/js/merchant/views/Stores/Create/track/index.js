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
      screen: 'create product stores',
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
    openModal: () => {
      triggerHotjarRecording('Store_Product_Creation');
    },
    init(_config) {
      setConfig(_config);
    },
    productName: (text) => sendToSegment('add name', 'blur', { text }),
    addProductImage: () => sendToSegment('add image'),
    sellingPrice: (text) => sendToSegment('add selling price', 'blur', { text }),
    discountedPrice: (text) => sendToSegment('add discounted price', 'blur', { text }),
    productDescription: (text) => sendToSegment('add product description', 'blur', { text }),
    quantityAvailable: (text) => sendToSegment('add quantity available', 'blur', { text }),
    createProduct: () => sendToSegment('product save'),
    cancelBtn: () => sendToSegment('product cancel'),
  };
}

export default _track();
