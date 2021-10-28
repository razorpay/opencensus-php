import { triggerHotjarRecording } from 'common/utils/hotjar';

function _track() {
  return {
    open: () => {
      triggerHotjarRecording('Store_Creation');
    },
  };
}

export default _track();
