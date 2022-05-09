import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

const commonProp = {
  origin: 'dashboard',
};

function _track() {
  let track;

  function send(event, options) {
    track(trackLJ(`qr.${event}`, { ...options, ...commonProp }));
    trackSegment({
      event: `qr ${event}`,
      screen: 'list',
      options: { ...options, ...commonProp },
    });
  }

  return {
    field: ({ target }) => {
      const { name, value } = target;
      send(`search.${name}`, { value });
    },

    submit: () => send('search.submit'),

    clear: () => {
      send('search.clear');
    },

    success: () => send('success'),

    fail: (reason) => send('search.error', { response: reason }),

    browse: (type, options) => {
      const prop = {
        page: options.page,
      };
      send(`browse.${type}`, prop);
    },

    load: () => send('loaded'),

    create: () => send('create'),

    tour: () => send('tour'),

    tourStatus: (success) =>
      send('need_help.clicked', {
        success,
      }),

    docs: () => send('docs'),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
