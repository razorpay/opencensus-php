function _track() {
  let track;
  let buttonId;

  function send(event, options) {
    track(
      window.rzpQ.subscriptionButtons().interaction(`button.${event}`, {
        options,
        button_id: buttonId,
      }),
    );
  }

  return {
    trackDetailsStart() {
      send('details.start');
    },

    trackOptionsOpen() {
      send('details.options.open');
    },

    trackOptionsOpenEdit() {
      send('details.options.open.edit');
    },

    trackOptionsOpenDuplicate() {
      send('details.options.open.duplicate');
    },

    trackOptionsOpenSettings() {
      send('details.options.open.settings');
    },

    trackSettingsCustomMessage(message) {
      send('details.options.settings.custom_message', { message });
    },

    trackSettingsReceiptConfigure() {
      send('details.options.settings.receipt_configure');
    },

    trackSettingsCancel() {
      send('details.options.settings.cancel');
    },

    trackSettingsSaveFail(error) {
      send('details.options.settings.save_fail', { error });
    },

    trackSettingsSave() {
      send('details.options.settings.save');
    },

    trackOpenGetCodeModal() {
      send('details.review.get_code');
    },

    trackCopyCode() {
      send('details.review.copy_code');
    },

    trackCloseGetCodeModal() {
      send('details.review.close_code');
    },

    trackSeeDocumentation() {
      send('details.review.open_docs');
    },

    init: ({ track: _track, button_id }) => {
      track = _track;
      buttonId = button_id;
    },
  };
}

export default _track();
