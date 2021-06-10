import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track = () => {};

  function send(event, options) {
    track(
      trackLJ(`details.${event}`, options)
    );

    trackSegment({
      event,
      screen: 'details',
      options
    })
  }

  return {
    open: () => send('open'),

    close: () => send('close'),

    closeSuccess: (success, error) => send('close_success', { success, error }),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
