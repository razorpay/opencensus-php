import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

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
    open: () => {
      selfServeTrackInitiate({
        selfServeAction: 'Create QR code',
        page: 'Qr code',
        screen: 'QR Codes',
      });
      send('open');
    },

    field: (name, value) =>
      send(`field.${name}`, {
        value,
      }),

    advancedOptions: (isShow) => send('advance', { show: isShow }),

    submit: (formData) => send('submit', formData),

    cancel: () => send('cancel'),

    submitSuccess: () => {
      selfServeTrackSuccess({
        selfServeAction: 'Create QR code',
        page: 'Qr code',
        screen: 'QR Codes',
      });
      send('submit_success', {
        success: true,
      });
    },
    downloadImage: () => send('submit_success_download'),

    backToDashboard: () => send('back_to_dashboard'),

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
