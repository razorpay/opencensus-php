import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track;

  function send(event, options) {
    track(trackLJ(`qr.create.${event}`, options));

    trackSegment({
      event,
      screen: 'qr create',
      options,
    });
  }

  return {
    open: () => send('open'),

    field: (name, value) =>
      send(`field.${name}`, {
        value,
      }),

    advancedOptions: (isShow) => send('advance', { show: isShow }),

    submit: (formData) => send('submit', formData),

    cancel: () => send('cancel'),

    submitSuccess: () =>
      send('submit_success', {
        success: true,
      }),

    downloadImage: () => send('_submit_success_download'),

    backToDashboard: () => send('_submit_success_close'),

    needHelp: () => send('need_help.clicked'),

    submitFail: (reason) =>
      send('submit_fail', {
        fail: true,
        reason,
      }),

    // eslint-disable-next-line no-shadow
    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
