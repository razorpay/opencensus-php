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

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
