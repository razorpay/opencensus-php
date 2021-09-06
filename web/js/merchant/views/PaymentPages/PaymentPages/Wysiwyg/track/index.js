import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};
  let config = {
    payment_page_id: '',
  };

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`pp.create.${event}`, {
        data,
        config,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  function setConfig(newConfig) {
    config = {
      ...config,
      ...newConfig,
    };
  }

  return {
    publishPaymentPage: (label) => sendToSegment(label, 'clicked'),

    plugins: {
      save: (fb, ga) => {
        sendToLumberjack('plugins.save', { fb, ga });
        sendToSegment('plugins', 'save', { fb, ga });
      },
    },

    receipt: {
      open: () => {
        sendToSegment('payment receipts', 'clicked');
        sendToLumberjack('receipt.open_settings');
      },
      close: () => {
        sendToLumberjack('receipt.close_settings');
        sendToSegment('receipts', 'close');
      },
      save: (automatedReceipts, input_field, details_80G) => {
        sendToLumberjack('receipt.save', { automatedReceipts, input_field, details_80G });
        sendToSegment('receipts', 'saved', { automatedReceipts, input_field, details_80G });
      },
      clickSendingOption: (label) => {
        sendToLumberjack(`receipt.${label}`);
        sendToSegment(`receipts ${label}`, 'clicked');
      },
      check80GDetails: (label) => {
        sendToSegment('receipts 80-G', 'clicked');
        sendToLumberjack(`receipt.${label}`);
      },
      checkInputFields: () => sendToSegment('receipts input field', 'chosen'),
    },

    modal80G: {
      open: () => {
        sendToLumberjack('receipt.80g_details_start');
        sendToSegment('receipts 80-G', 'open');
      },
      close: () => {
        sendToLumberjack('receipt.80g_details_close');
        sendToSegment('receipts 80-G', 'close');
      },
      uploadStart: () => {
        sendToLumberjack('receipt.80g_upload_start');
        sendToSegment('receipts 80-G', 'upload start');
      },
      uploadSave: () => {
        sendToLumberjack('receipt.80g_upload_save');
        sendToSegment('receipts 80-G', 'upload save');
      },
      removeSignature: () => {
        sendToLumberjack('receipt.80g_upload_remove');
        sendToSegment('receipts 80-G', 'upload remove');
      },
      save: () => {
        sendToLumberjack('receipt.80g_details_save');
        sendToSegment('receipts 80-G', 'saved');
      },
      uploadFail: (error) => {
        sendToLumberjack('receipt.80g_upload_fail', { error });
        sendToSegment('receipts 80-G', 'upload fail', { error });
      },
    },

    settings: {
      open: () => {
        sendToLumberjack('settings.open');
        sendToSegment('settings', 'clicked');
      },
      save: (expiry, customMessage, redirect) => {
        sendToLumberjack('settings.save', { expiry, customMessage, redirect });
        sendToSegment('settings', 'saved');
      },
      close: () => sendToSegment('settings', 'closed'),
      checkCustomMessage: (value) => {
        let label = value ? 'checked' : 'unchecked';
        sendToLumberjack(`settings.custom_message.${label}`);
        sendToSegment('settings', `custom message ${label}`);
      },
      checkRedirect: (value) => {
        let label = value ? 'checked' : 'unchecked';
        sendToLumberjack(`settings.redirect.${label}`);
        sendToSegment('settings', `redirect ${label}`);
      },
      clickConfigurePlugins: () => {
        sendToLumberjack(`settings.plugins.configure`);
        sendToSegment('settings', `configure plugins`);
      },
      clickCreateHyperlinkButton: () => {
        sendToLumberjack(`settings.button.create`);
        sendToSegment('settings', `create hyperlink`);
      },
      clickExpiryDate: () => sendToSegment('settings expiry', 'added'),
    },

    success: {
      clickCopyUrl: () => {
        sendToLumberjack(`success.copy_url`);
        sendToSegment('success', 'copy url');
      },
      clickShareUrlViaEmailorSMS: (trackData) => {
        sendToLumberjack(`success.share.sms_email`, { trackData });
        sendToSegment('success', 'share via email/sms', { modes: trackData });
      },
      clickShareUrlViaSocialMedia: (platform) => {
        sendToLumberjack(`success.share.${platform}`);
        sendToSegment('success', `share via ${platform}`);
      },
      getHyperlinkButton: () => sendToSegment('success', 'get hyperlink'),
    },

    init(_lumberjackTrack, config) {
      lumberjackTrack = _lumberjackTrack;

      setConfig(config);
    },
  };
}

export default _track();
