import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track = () => {};

  function send(event, options) {
    track(
      trackLJ(`create.${event}`, options)
    );

    trackSegment({
      event,
      screen: 'create',
      options
    })
  }

  return {
    open: () => send('open'),

    field: (name, value) => send(`field.${name}`, {
      value
    }),

    advancedOptions: (isShow) => send('advance', { show: isShow }),

    submit: (formData) => send('submit', formData),

    cancel: () => send('cancel'),

    submitSuccess: (success) => send('submit_success', {
      success: true
    }),

    downloadImage: () => send('image_preview.download'),

    backToDashboard: () => send('image_preview.back_to_dashboard'),

    submitFail: (reason) => send('submit_fail', {
      fail: true,
      reason
    }),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
