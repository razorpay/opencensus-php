import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track;

  function send(event, options) {
    track(trackLJ(`${event}`, options));

    trackSegment({
      event,
      screen: 'list',
      options,
    });
  }

  return {
    field: ({ target }) => {
      const { name, value } = target;

      send(`search.${name}`, { value });
    },

    submit: () => send('submit'),

    clear: () => send('clear'),

    success: () => send('success'),

    fail: (reason) => send('fail', { reason }),

    browse: (type, options) => send(`list.${type}`, options),

    load: () => send('loaded'),

    create: () => send('create'),

    tour: () => send('tour'),

    tourStatus: (success) =>
      send('qr.need_help.clicked', {
        success,
      }),

    docs: () => send('qr.docs'),

    // eslint-disable-next-line no-shadow
    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
