import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let lumberjackTrack = () => {};

  function send(event, options) {
    lumberjackTrack(trackLJ(`details.${event}`, options));

    trackSegment({
      event,
      screen: 'details',
      options,
    });
  }

  return {
    open: () => send('open'),

    close: () => send('close'),

    closeSuccess: (success, error) => send('close_success', { success, error }),

    init: (_lumberjackTrack) => {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
