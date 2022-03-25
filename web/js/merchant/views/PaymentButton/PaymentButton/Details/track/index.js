import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, humanize } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};
  let buttonId;

  function sendToLumberjack(event, options) {
    lumberjackTrack(
      window.rzpQ.paymentButtons().interaction(`button.details.${event}`, {
        options,
        button_id: buttonId,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'details payment button',
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        buttonId,
      },
    });
  }

  return {
    detailsStart: () => {
      sendToLumberjack('start');
      sendToSegment('details', 'open');
    },
    optionsOpen: () => {
      sendToLumberjack('options.open');
      sendToSegment('options configurations', 'open');
    },
    optionsOpenEdit: () => {
      sendToLumberjack('options.open.edit');
      sendToSegment('options edit', 'click');
    },
    optionsOpenDuplicate: () => {
      sendToLumberjack('options.open.duplicate');
      sendToSegment('options duplicate', 'click');
    },
    optionsOpenSettings: (via) => {
      sendToLumberjack('options.open.settings', { via });
      sendToSegment('open settings', 'click', { via });
    },
    settingsCustomMessage: (via, message) => {
      sendToLumberjack('options.settings.custom_message', { via, message });
      sendToSegment('settings custom message', 'input', { via, message });
    },
    settingsReceiptConfigure: () => {
      sendToLumberjack('options.settings.receipt_configure');
      sendToSegment('receipts modal', 'open');
    },
    settingsCancel: () => {
      sendToLumberjack('options.settings.cancel');
      sendToSegment('settings modal cancel', 'click');
    },
    settingsSaveFail: (error) => {
      sendToLumberjack('options.settings.save_fail', { error });
      sendToSegment('settings save', 'fail', { error });
    },
    settingsSave: () => {
      sendToLumberjack('options.settings.save');
      sendToSegment('settings save', 'success');
    },
    openGetCodeModal: () => {
      sendToLumberjack('review.get_code');
      sendToSegment('get code modal', 'open');
    },
    copyCode: () => {
      sendToLumberjack('review.copy_code');
      sendToSegment('get code copy code', 'click', {}, true);
    },
    closeGetCodeModal: () => {
      sendToLumberjack('review.close_code');
      sendToSegment('get code modal', 'close');
    },
    seeDocumentation: () => {
      sendToLumberjack('review.open_docs');
      sendToSegment('get code documentation', 'click');
    },
    testButton: () => {
      sendToLumberjack('review.test_button');
      sendToSegment('success test button', 'click', {}, true);
    },
    paymentReceiptsOpen: () => {
      sendToLumberjack('payment_receipts.open');
      sendToSegment('receipts modal', 'open');
    },
    receiptsType: (via, type) => {
      sendToLumberjack(`payment_receipts.type.${type}`, { via });
      sendToSegment(`receipts type ${type}`, 'select', { via });
    },
    inputFieldCheckbox: (via, status) => {
      const _status = status ? 'checked' : 'unchecked';

      sendToLumberjack(`payment_receipts.customer_input_field.${_status}`, { via });
      sendToSegment('receipts customer info', _status, { via });
    },
    details80gCheckbox: (via, status) => {
      const _status = status ? 'checked' : 'unchecked';

      sendToLumberjack(`payment_receipts.80g_details.${_status}`, {
        via,
      });
      sendToSegment('receipts 80G', _status, { via });
    },
    customMessageCheckbox: (via, status) => {
      const _status = status ? 'checked' : 'unchecked';

      sendToLumberjack(`button_settings.custom_message.${_status}`, {
        via,
      });
      sendToSegment('settings custom message', _status, { via });
    },
    redirectURLCheckbox: (via, status) => {
      const _status = status ? 'checked' : 'unchecked';

      sendToLumberjack(`button_settings.redirect_url.${_status}`, {
        via,
      });
      sendToSegment('settings redirect', _status, { via });
    },
    pluginClick: (name, is_direct_plugin) => {
      sendToLumberjack(`plugins.${name}`, { is_direct_plugin });
      sendToSegment(`${humanize(name)} documentation link`, 'click', { is_direct_plugin }, true);
    },

    init(_lumberjackTrack, _buttonId) {
      lumberjackTrack = _lumberjackTrack;
      buttonId = _buttonId;
    },
  };
}

export default _track();
