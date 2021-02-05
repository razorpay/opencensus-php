import uuid from 'uuid';
import moment from 'moment';

function _track() {
  let track = function () {};
  const defaultOptions = {};
  let local_order_id;
  let initialization_time;

  function send(event, options) {
    track(
      window.rzpQ.paymentLinks().interaction(`pl.create.${event}`, {
        ...defaultOptions,
        ...options,
        local_order_id,
        time_diff: moment.duration(moment().diff(initialization_time)).asSeconds(),
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
      firstPaymentMinAmount: () => send('first_payment_min_amount'),
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
      fail: (option) => send('fail', option),
    },

    init({ track: _track }) {
      track = _track;
      local_order_id = uuid();
      initialization_time = new Date();
    },
  };
}

export default _track();
