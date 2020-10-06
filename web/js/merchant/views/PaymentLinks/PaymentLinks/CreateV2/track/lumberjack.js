function _track() {
  let track = function () {};
  const defaultOptions = {};

  function send(event, options) {
    track(
      window.rzpQ.paymentLinks().interaction(`pl.create.${event}`, {
        ...defaultOptions,
        ...options,
      }),
    );
  }

  return {
    // Payment link type selection
    linkTypeSelection: {
      open() {
        send('template');
      },
      select(type) {
        send('initiate', {
          type,
        });

        defaultOptions.type = type;
      },
    },

    // Fields
    fields: {
      currency: () => send('currency'),
      amount: () => send('amount'),
      paymentFor: () => send('description'),
      partialPayment: () => send('partial_payment'),
      contact: () => send('mobile'),
      email: () => send('email'),
      notifySms: () => send('notify_sms'),
      notifyEmail: () => send('notify_email'),
      receipt: () => send('receipt'),
      expiryDate: () => send('expiry_date'),
      expiryTime: () => send('expiry_time'),
      notes: () => send('notes'),
      reminders: () => send('reminders'),
    },

    // Form
    form: {
      cancel: () => send('cancel'),
      cancelConfirm: (options) => send('cancel_confirm', options),
      create: () => send('issue'),
      success: () => send('success'),
      fail: () => send('fail'),
    },

    init({ track: _track }) {
      track = _track;
    },
  };
}

export default _track();
