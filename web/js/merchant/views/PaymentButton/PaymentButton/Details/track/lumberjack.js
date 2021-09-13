function _track() {
  let track, buttonId;

  function send(event, options) {
    track(
      window.rzpQ.paymentButtons().interaction(`button.${event}`, {
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

    trackOptionsOpenSettings(source) {
      send('details.options.open.settings', { via: source });
    },

    trackSettingsCustomMessage(via, message) {
      send('details.options.settings.custom_message', { message, via });
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

    trackTestButton() {
      send('details.review.test_button');
    },

    trackPaymentReceiptsOpen() {
      send('details.payment_receipts.open');
    },

    trackReceiptsType(via, type) {
      send(`details.payment_receipts.type.${type}`, { via });
    },

    trackInputFieldCheckbox(via, status) {
      send(`details.payment_receipts.customer_input_field.${status ? 'checked' : 'unchecked'}`, {
        via,
      });
    },

    track80gDetailsCheckbox(via, status) {
      send(`details.payment_receipts.80g_details.${status ? 'checked' : 'unchecked'}`, { via });
    },

    trackCustomMessageCheckbox(via, status) {
      send(`details.button_settings.custom_message.${status ? 'checked' : 'unchecked'}`, { via });
    },

    trackRedirectURLCheckbox(via, status) {
      send(`details.button_settings.redirect_url.${status ? 'checked' : 'unchecked'}`, { via });
    },

    trackPluginClick(plugin) {
      send(`details.plugins.${plugin}`);
    },

    // eslint-disable-next-line no-shadow
    init: ({ track: _track, button_id }) => {
      track = _track;
      buttonId = button_id;
    },
  };
}

export default _track();
