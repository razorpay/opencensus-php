function track() {
  let mode;
  let source;

  function send(type) {
    window.rzpQ.push(
      window.rzpQ.paymentLinks().interaction(`pl.switch.${type}`, {
        mode,
        source,
      }),
    );
  }

  return {
    banner: {
      knowMore: () => send('know_more_1'),
      switchNow: () => send('initiate'),
    },

    switchModal: {
      confirm: () => send('proceed'),
      cancel: () => send('back_1'),
    },

    confirmModal: {
      confirm: () => send('confirm'),
      cancel: () => send('back_2'),
      success: () => send('switch_success'),
      failure: () => send('switch_failure'),
    },

    init: function (_mode, _source) {
      mode = _mode;
      source = _source;
    },
  };
}

export default track();
