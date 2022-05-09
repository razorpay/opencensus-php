import { trackLJ, trackSegment } from 'merchant/views/QRCodes/track';

function _track() {
  let track;

  function send(event, options, actionName) {
    trackSegment({
      event: `qr details ${event}`,
      options,
      actionName,
      screen: 'qr code details view',
    });
    track(trackLJ(`qr.details.${event}`, options));
  }

  return {
    open: () => send('view'),

    preview: () => send('preview_qr'),

    downloadQR: () => send('download_qr'),

    close: () => send('status.close'),

    closeSuccess: (success, error) => send('close_success', { success, error }),

    viewAllPayments: () => send('view_payments'),

    unmountDetailsView: () => send('view.close'),

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
