import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track;

  function send(event, options) {
    track(trackLJ(`search.${event}`, options));

    trackSegment({
      event,
      screen: 'list',
      options,
    });
  }

  return {
    field: ({ target }) => {
      const { name, value } = target;

      send(name, { value });
    },

    submit: () => send('submit'),

    clear: () => send('clear'),

    success: () => send('success'),

    fail: (reason) => send('fail', { reason }),

    browse: (type, options) => send(`browse.${type}`, options),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
