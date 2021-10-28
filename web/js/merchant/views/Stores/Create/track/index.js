import { triggerHotjarRecording } from 'common/utils/hotjar';

function _track() {
  return {
    openModal: () => {
      triggerHotjarRecording('Store_Product_Creation');
    },
  };
}

export default _track();
