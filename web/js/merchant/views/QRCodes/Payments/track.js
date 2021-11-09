import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let lumberjackTrack = () => {};

  function send(event, options) {
    lumberjackTrack(trackLJ(`payments.${event}`, options));

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

    tour: () => send('tour'),

    tourStatus: (success) =>
      send('tour_response', {
        success,
      }),

    docs: () => send('docs'),

    init: (_lumberjackTrack) => {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
