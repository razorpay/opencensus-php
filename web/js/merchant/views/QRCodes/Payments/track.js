import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track;

  function send(event, options) {
    track(trackLJ(`qr.payments.${event}`, options));

    trackSegment({
      event: `qr payments ${event}`,
      screen: 'list',
      options,
    });
  }

  return {
    field: ({ target }) => {
      const { name, value } = target;

      send(`search.${name}`, { value });
    },

    submit: () => send('search.submit', { origin: 'dashboard' }),

    clear: () => send('search.clear', { origin: 'dashboard' }),

    success: () => send('success'),

    fail: (reason) => send('search.error', { origin: 'dashboard', response: reason }),

    browse: (type, options) => send(`browse.${type}`, options),

    load: () => send('loaded'),

    tour: () => send('tour'),

    tourStatus: (success) =>
      send('tour_response', {
        success,
      }),

    docs: () => send('docs'),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
